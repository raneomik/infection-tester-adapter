<?php

/**
 * This code is licensed under the BSD 3-Clause License.
 *
 * Copyright (c) 2017, Maks Rafalko
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Raneomik\InfectionTestFramework\Tester\Script;

use function array_unique;
use function array_values;
use function basename;
use function bin2hex;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use FilesystemIterator;
use function get_included_files;
use function getmypid;
use function implode;
use function is_dir;
use function mkdir;
use function preg_match;
use function random_bytes;
use function random_int;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageDriverDetector;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function register_shutdown_function;
use function rtrim;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;
use function serialize;
use SplFileInfo;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function strtolower;
use Throwable;

/**
 * Runtime coverage collector for Nette Tester jobs.
 * Starts coverage at script start and dumps on shutdown.
 *
 * Priority: PCOV > PHPDBG > Xdebug (as per Tester philosophy)
 */
final class CoverageRuntime
{
    /**
     * Cache for collected PHP files to avoid repeated filesystem scans.
     *
     * @var array<string, string[]>
     */
    private static array $phpFilesCache = [];

    /**
     * Cache for configured filters to avoid repeated Filter creation and population.
     *
     * @var array<string, Filter>
     */
    private static array $filterCache = [];

    /**
     * @param string $fragmentDir directory to write coverage fragments
     * @param string[] $srcDirs absolute paths of source directories to include
     */
    public static function start(string $fragmentDir, array $srcDirs): void
    {
        if ('' === $fragmentDir || [] === $srcDirs) {
            return;
        }

        if (!is_dir($fragmentDir)) {
            @mkdir($fragmentDir, 0777, true);
        }

        // Get or create cached filter
        $filter = self::getOrCreateFilter($srcDirs);

        if (null === $filter) {
            return;
        }

        $detector = new CoverageDriverDetector();
        $driver = $detector->buildCoverageDriver($filter);

        if (null === $driver) {
            return;
        }

        $coverage = new CodeCoverage($driver, $filter);

        $testId = self::detectTestFromIncludedFiles();

        $coverage->start($testId);

        register_shutdown_function(static function () use ($coverage, $fragmentDir): void {
            self::dumpCoverage($coverage, $fragmentDir);
        });
    }

    /**
     * Get or create a cached Filter for the given source directories.
     *
     * @param string[] $srcDirs
     */
    private static function getOrCreateFilter(array $srcDirs): ?Filter
    {
        $cacheKey = implode('|', $srcDirs);

        // Return cached filter if available
        if (isset(self::$filterCache[$cacheKey])) {
            return self::$filterCache[$cacheKey];
        }

        // Create new filter
        $filter = new Filter();
        $files = self::collectPhpFiles($srcDirs);

        if ([] === $files) {
            return null;
        }

        self::addFilesToFilter($filter, $files);

        // Cache the configured filter
        self::$filterCache[$cacheKey] = $filter;

        return $filter;
    }

    /**
     * Extract test identifier from a test file path.
     */
    private static function extractTestIdFromFile(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = @file_get_contents($filePath);

        if (false === $content) {
            return null;
        }

        // Extract namespace
        $namespace = '';

        if (0 < preg_match('/^\s*namespace\s+([a-zA-Z0-9_\\\\]+)\s*;/m', $content, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        $className = '';

        if (0 < preg_match('/^\s*(?:final\s+)?(?:abstract\s+)?class\s+([a-zA-Z0-9_]+)/m', $content, $matches)) {
            $className = $matches[1];
        }

        // TestCase format: extract method from --method=xxx in argv
        if ('' !== $className) {
            $methodName = self::extractMethodFromArgv();

            return sprintf(
                '%s::%s',
                '' !== $namespace ? $namespace . '\\' . $className : $className,
                $methodName,
            );
        }

        // PHPT procédural or test() function format
        // Use filename as identifier since there's no class
        $basename = basename($filePath, '.php');
        $basename = basename($basename, '.phpt');

        return sprintf(
            '%s::%s',
            '' !== $namespace ? $namespace . '\\' . $basename : $basename,
            'test',
        );
    }

    /**
     * Extract method name from argv arguments.
     * Supports: --method=methodName format.
     */
    private static function extractMethodFromArgv(): string
    {
        /** @var string $arg */
        foreach ($_SERVER['argv'] ?? [] as $arg) {// @phpstan-ignore-line
            if (0 < preg_match('/^--method=([a-zA-Z0-9_]+)$/', $arg, $matches)) {
                return $matches[1];
            }
        }

        return 'run';
    }

    /**
     * Collect all PHP files from the given source directories.
     * Uses cache to avoid repeated filesystem scans.
     *
     * @param string[] $srcDirs
     *
     * @return string[]
     */
    private static function collectPhpFiles(array $srcDirs): array
    {
        // Create cache key from sorted source directories
        $cacheKey = implode('|', $srcDirs);

        // Return cached result if available
        if (isset(self::$phpFilesCache[$cacheKey])) {
            return self::$phpFilesCache[$cacheKey];
        }

        $allFiles = [];

        // Collect files from all directories (avoid array_merge in loop for performance)
        foreach ($srcDirs as $dir) {
            foreach (self::scanDirectoryForPhpFiles($dir) as $file) {
                $allFiles[] = $file;
            }
        }

        // Remove duplicates if source directories overlap (rare case)
        $result = array_values(array_unique($allFiles));

        // Cache the result for subsequent tests
        self::$phpFilesCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Scan a single directory recursively for PHP files.
     *
     * @return string[]
     */
    private static function scanDirectoryForPhpFiles(string $dir): array
    {
        $dir = rtrim($dir, '/');

        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            // Only process regular files with .php extension
            if ($file->isFile() && 'php' === strtolower($file->getExtension())) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Add files to the filter using the appropriate method.
     *
     * @param string[] $files
     */
    private static function addFilesToFilter(Filter $filter, array $files): void
    {
        // Modern php-code-coverage versions support includeFiles for batch operations
        // Convert to list to satisfy type requirements
        $filter->includeFiles(array_values($files));
    }

    /**
     * Detect test identifier from included files.
     * This is the most reliable way since the test file is always included.
     */
    private static function detectTestFromIncludedFiles(): string
    {
        $includedFiles = get_included_files();

        foreach ($includedFiles as $file) {
            // Skip vendor files
            if (str_contains($file, '/vendor/')) {
                continue;
            }

            // Look for test files
            if (str_ends_with($file, 'Test.php') || str_ends_with($file, 'test.php')) {
                $testId = self::extractTestIdFromFile($file);

                if (null !== $testId) {
                    return $testId;
                }
            }
        }

        return 'global-coverage';
    }

    /**
     * Dump coverage data to a fragment file.
     */
    private static function dumpCoverage(CodeCoverage $coverage, string $fragmentDir): void
    {
        try {
            $coverage->stop();
        } catch (Throwable) {
            // Ignore errors during stop
        }

        $filename = self::generateFragmentFilename();
        $path = rtrim($fragmentDir, '/') . '/' . $filename;

        @file_put_contents($path, serialize($coverage));
    }

    /**
     * Generate a unique filename for a coverage fragment.
     */
    private static function generateFragmentFilename(): string
    {
        $pid = getmypid() ?: random_int(1, 999999);
        $random = bin2hex(random_bytes(4));

        return "cc-{$pid}-{$random}.phpser";
    }
}
