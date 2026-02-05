<?php

declare(strict_types=1);

namespace MikeBronner\DevelopmentSettings;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ComposerPlugin implements EventSubscriberInterface, PluginInterface
{
    private const PACKAGE_NAME = 'mikebronner/development-settings';
    private const MANIFEST_FILE = 'manifest.json';
    private const BOX_WIDTH = 80;

    private static bool $dependenciesInjected = false;
    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'publish',
            ScriptEvents::POST_UPDATE_CMD => 'publish',
        ];
    }

    public function publish(Event $event): void
    {
        $this->doPublish($event->getIO());
    }

    private function doPublish(IOInterface $io): void
    {
        $packageDir = $this->getPackageDir();

        if (! $packageDir) {
            $io->writeError('<error>Could not locate developer-settings package directory</error>');

            return;
        }

        $projectDir = getcwd();
        $manifest = $this->loadManifest($packageDir . '/' . self::MANIFEST_FILE);
        $config = require $packageDir . '/config/developer-settings.php';

        $filesToPublish = $this->discoverFiles($packageDir, $config['paths']);
        $stats = ['new' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'removed' => 0];
        $changedFiles = [];

        $this->writeBoxHeader($io);

        foreach ($filesToPublish as $relativePath => $sourceFile) {
            $destinationPath = $this->getDestinationPath($relativePath);
            $destinationFile = $projectDir . '/' . $destinationPath;
            $knownChecksums = $manifest[$destinationPath] ?? [];

            if (! file_exists($destinationFile)) {
                $this->copyFile($sourceFile, $destinationFile);
                $io->write($this->formatOutputLine(type: 'created', path: $destinationPath));
                $stats['new']++;
                $changedFiles[] = $destinationPath;

                continue;
            }

            $localChecksum = md5_file($destinationFile);
            $sourceChecksum = md5_file($sourceFile);

            if ($localChecksum === $sourceChecksum) {
                $stats['unchanged']++;

                continue;
            }

            if (! in_array($localChecksum, $knownChecksums, true)) {
                if ($this->promptForLocallyModifiedFile($io, $destinationPath)) {
                    $this->copyFile($sourceFile, $destinationFile);
                    $io->write($this->formatOutputLine(type: 'updated', path: $destinationPath));
                    $stats['updated']++;
                    $changedFiles[] = $destinationPath;

                    continue;
                }

                $io->write($this->formatOutputLine(type: 'skipped', path: $destinationPath));
                $stats['skipped']++;

                continue;
            }

            $this->copyFile($sourceFile, $destinationFile);
            $io->write($this->formatOutputLine(type: 'updated', path: $destinationPath));
            $stats['updated']++;
            $changedFiles[] = $destinationPath;
        }

        $removedFiles = $this->removeOrphanedFiles($projectDir, $manifest, $filesToPublish);

        foreach ($removedFiles as $removedPath) {
            $io->write($this->formatOutputLine(type: 'removed', path: $removedPath));
            $stats['removed']++;
            $changedFiles[] = $removedPath;
        }

        $composerConfig = $config['composer'] ?? [];
        $dependencyResult = $this->prepareComposerDependencies(
            install: $composerConfig['install'] ?? [],
            remove: $composerConfig['remove'] ?? [],
        );

        foreach (array_keys($dependencyResult['toInstall']) as $package) {
            $io->write($this->formatOutputLine(type: 'dep_added', path: $package));
            $stats['new']++;
        }

        $stats['unchanged'] += count($dependencyResult['unchanged']);

        foreach ($dependencyResult['toRemove'] as $package) {
            $io->write($this->formatOutputLine(type: 'dep_removed', path: $package));
            $stats['removed']++;
        }

        $this->writeBoxFooter($io, $stats);
        $this->runHooks($io, $config['hooks'], $changedFiles);
        $this->installDevDependencies($io, $dependencyResult['toInstall']);
        $this->removeDevDependencies($io, $dependencyResult['toRemove']);
    }

    private function writeBoxHeader(IOInterface $io): void
    {
        $border = 'fg=gray';

        $io->write('');
        $io->write("<{$border}>┌" . str_repeat('─', self::BOX_WIDTH - 2) . '┐</>');
        $io->write("<{$border}>│</>  <fg=cyan>Developer Settings</>" . str_repeat(' ', self::BOX_WIDTH - 24) . "  <{$border}>│</>");
        $io->write("<{$border}>├" . str_repeat('─', self::BOX_WIDTH - 2) . '┤</>');
    }

    private function writeBoxFooter(IOInterface $io, array $stats): void
    {
        $border = 'fg=gray';

        $io->write("<{$border}>├" . str_repeat('─', self::BOX_WIDTH - 2) . '┤</>');

        $summaryParts = [
            $this->formatSummaryItem($stats['new'], 'new', 'green'),
            $this->formatSummaryItem($stats['updated'], 'updated', 'yellow'),
            $this->formatSummaryItem($stats['unchanged'], 'unchanged', 'gray', 'white'),
            $this->formatSummaryItem($stats['skipped'], 'skipped', 'red'),
            $this->formatSummaryItem($stats['removed'], 'removed', 'magenta'),
        ];

        $summary = implode(' · ', $summaryParts);
        $summaryPlain = preg_replace('/<[^>]+>/', '', $summary);
        $padding = self::BOX_WIDTH - 6 - mb_strlen($summaryPlain);
        $io->write("<{$border}>│</>  " . $summary . str_repeat(' ', $padding) . "  <{$border}>│</>");

        $io->write("<{$border}>└" . str_repeat('─', self::BOX_WIDTH - 2) . '┘</>');
        $io->write('');
    }

    private function formatSummaryItem(int $count, string $label, string $bgColor, ?string $fgColor = null): string
    {
        if ($count === 0) {
            return "<fg=gray>{$count} {$label}</>";
        }

        $fgColor ??= "bright-{$bgColor}";

        return "<fg={$fgColor};bg={$bgColor}> {$count} {$label} </>";
    }

    private function formatOutputLine(string $type, string $path): string
    {
        $formats = [
            'created' => ['icon' => '+', 'style' => 'info'],
            'updated' => ['icon' => '↻', 'style' => 'comment'],
            'unchanged' => ['icon' => '=', 'style' => null],
            'skipped' => ['icon' => '○', 'style' => 'fg=red'],
            'removed' => ['icon' => '-', 'style' => 'fg=magenta'],
            'dep_added' => ['icon' => '+', 'style' => 'info', 'suffix' => ' (composer)'],
            'dep_removed' => ['icon' => '-', 'style' => 'fg=magenta', 'suffix' => ' (composer)'],
            'dep_updated' => ['icon' => '↻', 'style' => 'comment', 'suffix' => ' (composer)'],
            'dep_unchanged' => ['icon' => '=', 'style' => null, 'suffix' => ' (composer)'],
        ];

        $format = $formats[$type] ?? ['icon' => ' ', 'style' => null, 'suffix' => ''];
        $style = $format['style'] ?? null;
        $prefix = $style !== null
            ? "<{$style}>{$format['icon']}</{$style}>"
            : $format['icon'];
        $suffix = $format['suffix'] ?? '';

        $displayPath = match ($type) {
            'skipped' => "{$path} (locally modified)",
            default => $path . $suffix,
        };

        $prefixLength = 1;
        $maxPathLength = self::BOX_WIDTH - 6 - $prefixLength - 2 - 1;

        if (strlen($displayPath) > $maxPathLength) {
            $displayPath = substr($displayPath, 0, $maxPathLength - 3) . '...';
        }

        $visibleLength = $prefixLength + 2 + strlen($displayPath);
        $padding = max(1, self::BOX_WIDTH - 6 - $visibleLength);

        return '<fg=gray>│</>  ' . $prefix . '  ' . $displayPath . str_repeat(' ', $padding) . '  <fg=gray>│</>';
    }

    private function prepareComposerDependencies(array $install, array $remove): array
    {
        if (self::$dependenciesInjected) {
            return ['toInstall' => [], 'unchanged' => [], 'toRemove' => []];
        }

        $composerFile = getcwd() . '/composer.json';
        $composerJson = json_decode(file_get_contents($composerFile), associative: true);
        $requireDev = $composerJson['require-dev'] ?? [];

        $packagesToInstall = [];
        $packagesUnchanged = [];
        $packagesToRemove = [];

        foreach ($install as $package => $version) {
            if (isset($requireDev[$package])) {
                $packagesUnchanged[$package] = $version;

                continue;
            }

            $packagesToInstall[$package] = $version;
            $requireDev[$package] = $version;
        }

        foreach ($remove as $package) {
            if (! isset($requireDev[$package])) {
                continue;
            }

            $packagesToRemove[] = $package;
            unset($requireDev[$package]);
        }

        if ($packagesToInstall !== [] || $packagesToRemove !== []) {
            self::$dependenciesInjected = true;

            ksort($requireDev);
            $composerJson['require-dev'] = $requireDev;
            file_put_contents(
                $composerFile,
                json_encode($composerJson, flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
            );
        }

        return ['toInstall' => $packagesToInstall, 'unchanged' => $packagesUnchanged, 'toRemove' => $packagesToRemove];
    }

    private function installDevDependencies(IOInterface $io, array $packages): void
    {
        if ($packages === []) {
            return;
        }

        $packageNames = implode(' ', array_keys($packages));
        $result = $this->executeCommand("composer update {$packageNames} --dev --no-interaction");

        if ($result === 0) {
            $io->write('<info>  + Dev dependencies installed successfully.</info>');
        } else {
            $io->writeError('<error>  + Failed to install dev dependencies. Run "composer update" manually.</error>');
        }

        $io->write('');
    }

    private function removeDevDependencies(IOInterface $io, array $packages): void
    {
        if ($packages === []) {
            return;
        }

        $packageNames = implode(' ', $packages);
        $result = $this->executeCommand("composer remove {$packageNames} --dev --no-interaction");

        if ($result === 0) {
            $io->write('<fg=magenta>  - Dev dependencies removed successfully.</>');
        } else {
            $io->writeError('<error>  - Failed to remove dev dependencies. Run "composer remove" manually.</error>');
        }

        $io->write('');
    }

    private function removeOrphanedFiles(string $projectDir, array $manifest, array $discoveredFiles): array
    {
        $removed = [];
        $discoveredPaths = array_keys($discoveredFiles);

        foreach (array_keys($manifest) as $manifestPath) {
            if (in_array($manifestPath, $discoveredPaths, true)) {
                continue;
            }

            $filePath = $projectDir . '/' . $manifestPath;

            if (! file_exists($filePath)) {
                continue;
            }

            unlink($filePath);
            $this->removeEmptyDirectories(dirname($filePath), $projectDir);
            $removed[] = $manifestPath;
        }

        return $removed;
    }

    private function removeEmptyDirectories(string $directory, string $stopAt): void
    {
        while ($directory !== $stopAt && is_dir($directory)) {
            $files = array_diff(scandir($directory) ?: [], ['.', '..']);

            if ($files !== []) {
                break;
            }

            rmdir($directory);
            $directory = dirname($directory);
        }
    }

    private function runHooks(IOInterface $io, array $hook, array $changedFiles): void
    {
        if ($changedFiles === []) {
            return;
        }

        $patterns = $hook['patterns'] ?? [];
        $command = $hook['command'] ?? null;
        $description = $hook['description'] ?? $command;

        if ($command === null) {
            return;
        }

        foreach ($changedFiles as $file) {
            if (! $this->matchesPattern($file, $patterns)) {
                continue;
            }

            $io->write("  <info>{$description}</info> ", false);

            $result = $this->executeCommand($command);

            if ($result === 0) {
                $io->write('<info>done</info>');
            } else {
                $io->write('<error>failed</error>');
            }

            break;
        }
    }

    private function matchesPattern(string $file, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $regex = $this->globToRegex($pattern);

            if (preg_match($regex, $file)) {
                return true;
            }
        }

        return false;
    }

    private function globToRegex(string $pattern): string
    {
        $regex = preg_quote($pattern, '/');
        $regex = str_replace('\*\*', '.*', $regex);
        $regex = str_replace('\*', '[^\/]*', $regex);
        $regex = str_replace('\?', '.', $regex);

        return '/^' . $regex . '$/';
    }

    private function executeCommand(string $command): int
    {
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            getcwd(),
        );

        if (! is_resource($process)) {
            return 1;
        }

        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }

    private function promptForLocallyModifiedFile(IOInterface $io, string $path): bool
    {
        if (! $io->isInteractive()) {
            return false;
        }

        $result = $io->askConfirmation(
            question: "<fg=gray>│</>  <fg=yellow>⚠</>  {$path} — discard local changes? [y/<options=bold>N</>] ",
            default: false,
        );

        $io->write("\033[1A\033[2K", newline: false);

        return $result;
    }

    private function getPackageDir(): ?string
    {
        $vendorDir = getcwd() . '/vendor/' . self::PACKAGE_NAME;

        if (is_dir($vendorDir)) {
            return realpath($vendorDir);
        }

        return null;
    }

    private function loadManifest(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);

        return json_decode($content, true) ?? [];
    }

    private function discoverFiles(string $packageDir, array $paths): array
    {
        $files = [];

        foreach ($paths['directories'] as $directory) {
            $sourceDir = $packageDir . '/' . $directory;

            if (! is_dir($sourceDir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
            );

            foreach ($iterator as $file) {
                $relativePath = $directory
                    . '/'
                    . substr($file->getPathname(), strlen($sourceDir) + 1);
                $files[$relativePath] = $file->getPathname();
            }
        }

        foreach ($paths['files'] as $filePath) {
            $sourceFile = "{$packageDir}/{$filePath}";

            if (! file_exists($sourceFile)) {
                continue;
            }

            $files[$filePath] = $sourceFile;
        }

        return $files;
    }

    private function getDestinationPath(string $relativePath): string
    {
        return $relativePath;
    }

    private function copyFile(string $source, string $destination): void
    {
        $directory = dirname($destination);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        copy($source, $destination);
    }
}
