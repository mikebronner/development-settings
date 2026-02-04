<?php

declare(strict_types=1);

namespace Insight\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures that enum case names are all uppercase.
 *
 * This sniff checks that all enum cases follow SCREAMING_SNAKE_CASE convention.
 * For example: GET, POST, SOME_CASE are valid, but get, Post, someCase are not.
 */
final class UpperCaseEnumCaseSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int, int>
     */
    public function register(): array
    {
        return [T_ENUM];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Find the opening brace of the enum
        $enumStart = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPtr + 1);

        if ($enumStart === false) {
            // Enum has no body, nothing to check
            return;
        }

        // Find the matching closing brace
        $enumEnd = $tokens[$enumStart]['bracket_closer'] ?? null;

        if ($enumEnd === null) {
            // Can't find the closing brace, try alternative method
            $enumEnd = $phpcsFile->findNext(T_CLOSE_CURLY_BRACKET, $enumStart + 1);
            if ($enumEnd === false) {
                return;
            }
        }

        // Find all enum case statements within the enum (use T_ENUM_CASE, not T_CASE)
        $currentPos = $enumStart + 1;
        while ($currentPos < $enumEnd) {
            // PHPCS uses custom token T_ENUM_CASE for enum cases
            $caseToken = $phpcsFile->findNext(T_ENUM_CASE, $currentPos, $enumEnd);

            if ($caseToken === false) {
                // No more case statements
                break;
            }

            // The next non-whitespace token after T_ENUM_CASE should be the case name (T_STRING)
            $caseNamePtr = $phpcsFile->findNext(
                T_WHITESPACE,
                $caseToken + 1,
                null,
                true
            );

            if ($caseNamePtr === false) {
                $currentPos = $caseToken + 1;
                continue;
            }

            // Make sure it's a T_STRING token (the case name)
            if ($tokens[$caseNamePtr]['code'] !== T_STRING) {
                $currentPos = $caseToken + 1;
                continue;
            }

            $caseName = $tokens[$caseNamePtr]['content'];

            // Check if the case name is uppercase
            if ($this->isUppercase($caseName) === false) {
                $expectedName = $this->toUppercase($caseName);

                $error = 'Enum case names must be uppercase; expected %s but found %s';
                $data = [
                    $expectedName,
                    $caseName,
                ];

                $fix = $phpcsFile->addFixableError($error, $caseNamePtr, 'EnumCaseNotUpperCase', $data);

                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    $phpcsFile->fixer->replaceToken($caseNamePtr, $expectedName);
                    $phpcsFile->fixer->endChangeset();
                }
            }

            $currentPos = $caseToken + 1;
        }
    }

    /**
     * Check if a string is entirely uppercase (allows underscores and numbers).
     *
     * @param string $string The string to check.
     *
     * @return bool
     */
    protected function isUppercase(string $string): bool
    {
        return strtoupper($string) === $string;
    }

    /**
     * Convert a string to uppercase.
     *
     * Handles camelCase and PascalCase by inserting underscores before capitals.
     *
     * @param string $string The string to convert.
     *
     * @return string
     */
    protected function toUppercase(string $string): string
    {
        // If already has underscores, just uppercase it
        if (strpos($string, '_') !== false) {
            return strtoupper($string);
        }

        // Convert camelCase/PascalCase to SCREAMING_SNAKE_CASE
        // Insert underscore before uppercase letters (except the first character)
        $result = preg_replace('/(?<!^)([A-Z])/', '_$1', $string);

        return strtoupper($result);
    }
}
