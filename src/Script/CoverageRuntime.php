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
use function bin2hex;
use function file_put_contents;
use FilesystemIterator;
use function get_included_files;
use function getmypid;
use function implode;
use function is_dir;
use function mkdir;
use function random_bytes;
use function random_int;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageDriverProvider;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoveringTestIdentifier;
use function realpath;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function register_shutdown_function;
use function rtrim;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Test\TestStatus\TestStatus;
use function serialize;
use SplFileInfo;
use function sprintf;
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

    private readonly CoveringTestIdentifier $coveringTestIdentifier;

    private readonly CoverageDriverProvider $coverageDriverProvider;

    /**
     * @param string[] $srcDirs
     */
    private function __construct(
        private readonly string $fragmentDir,
        private readonly array $srcDirs,
    ) {
        $this->coveringTestIdentifier = new CoveringTestIdentifier(get_included_files());
        $this->coverageDriverProvider = new CoverageDriverProvider();
    }

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

        $runtime = new self($fragmentDir, $srcDirs);
        $runtime->cover();
    }

    private function cover(): void
    {
        $filter = $this->getOrCreateFilter();

        if (null === $filter) {
            return;
        }

        $driver = $this->coverageDriverProvider->coverageDriver($filter);

        if (null === $driver) {
            return;
        }

        $codeCoverage = new CodeCoverage($driver, $filter);

        $codeCoverage->start(
            $this->coveringTestIdentifier->identifyTest(),
            $this->coveringTestIdentifier->identifySize(),
        );

        register_shutdown_function(function () use ($codeCoverage): void {
            $this->dumpCoverage($codeCoverage);
        });
    }

    /**
     * Get or create a cached Filter for the given source directories.
     */
    private function getOrCreateFilter(): ?Filter
    {
        $cacheKey = implode('|', $this->srcDirs);

        // Return cached filter if available
        if (isset(self::$filterCache[$cacheKey])) {
            return self::$filterCache[$cacheKey];
        }

        $filter = new Filter();
        $files = $this->collectPhpFiles();

        if ([] === $files) {
            return null;
        }

        $this->addFilesToFilter($filter, $files);

        // Cache the configured filter
        self::$filterCache[$cacheKey] = $filter;

        return $filter;
    }

    /**
     * Collect all PHP files from the given source directories.
     * Uses cache to avoid repeated filesystem scans.
     *
     * @return string[]
     */
    private function collectPhpFiles(): array
    {
        // Create cache key from sorted source directories
        $cacheKey = implode('|', $this->srcDirs);

        // Return cached result if available
        if (isset(self::$phpFilesCache[$cacheKey])) {
            return self::$phpFilesCache[$cacheKey];
        }

        $allFiles = [];

        // Collect files from all directories (avoid array_merge in loop for performance)
        foreach ($this->srcDirs as $srcDir) {
            foreach ($this->scanDirectoryForPhpFiles($srcDir) as $file) {
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
    private function scanDirectoryForPhpFiles(string $dir): array
    {
        $dir = realpath(rtrim($dir, '/'));

        if (false === $dir) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()
                && 'php' === strtolower($file->getExtension())
                && false !== $realpath = $file->getRealPath()
            ) {
                $files[] = $realpath;
            }
        }

        return $files;
    }

    /**
     * Add files to the filter using the appropriate method.
     *
     * @param string[] $files
     */
    private function addFilesToFilter(Filter $filter, array $files): void
    {
        // Modern php-code-coverage versions support includeFiles for batch operations
        // Convert to list to satisfy type requirements
        $filter->includeFiles(array_values($files));
    }

    /**
     * Dump coverage data to a fragment file.
     */
    private function dumpCoverage(CodeCoverage $codeCoverage): void
    {
        try {
            $codeCoverage->stop(true, TestStatus::success());
        } catch (Throwable) {
            // Ignore errors during stop
        }

        $filename = $this->generateFragmentFilename();
        $path = rtrim($this->fragmentDir, '/') . '/' . $filename;

        @file_put_contents($path, serialize($codeCoverage));
    }

    /**
     * Generate a unique filename for a coverage fragment.
     */
    private function generateFragmentFilename(): string
    {
        $pid = getmypid() ?: random_int(1, 999999);
        $random = bin2hex(random_bytes(4));

        return sprintf('cc-%s-%s.phpser', $pid, $random);
    }
}
