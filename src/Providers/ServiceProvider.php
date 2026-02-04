<?php

declare(strict_types=1);

namespace MikeBronner\DevelopmentSettings\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

final class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $publishPaths = [];

        foreach (config('developer-settings.paths.directories') as $directory) {
            $publishPaths[__DIR__ . '/../../' . $directory] = base_path($directory);
        }

        foreach (config('developer-settings.paths.files') as $file) {
            $publishPaths[__DIR__ . '/../../' . $file] = base_path($file);
        }

        $this->publishes(paths: $publishPaths, groups: 'developer-settings');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            path: __DIR__ . '/../../config/developer-settings.php',
            key: 'developer-settings',
        );
    }
}
