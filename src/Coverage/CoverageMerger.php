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

namespace Raneomik\InfectionTestFramework\Tester\Coverage;

use function class_exists;
use function file_get_contents;
use function fwrite;
use function glob;
use function is_file;
use function is_string;
use function mkdir;
use function rtrim;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Xml\Facade as PhpUnitXmlFacade;
use const STDERR;
use function unserialize;

/**
 * Merges coverage fragments and normalizes JUnit XML for Infection compatibility.
 */
final class CoverageMerger
{
    /**
     * Merge coverage fragments and normalize JUnit XML.
     *
     * @param string $fragmentDir Directory containing coverage fragments
     * @param string $outDir Directory to write merged coverage XML
     * @param string|null $junitPath Optional JUnit XML path to normalize
     *
     * @return int Exit code (0 = success)
     */
    public static function merge(string $fragmentDir, string $outDir, ?string $junitPath = null): int
    {
        // Step 1: Merge all fragments
        $merged = self::mergeFragments($fragmentDir);

        if (null === $merged) {
            fwrite(STDERR, "No valid coverage fragments found\n");

            return 4;
        }

        // Step 2: Write coverage XML
        self::writeCoverageXml($merged, $outDir);

        // Step 3: Normalize JUnit and replace ::run with real test methods
        if (is_string($junitPath) && is_file($junitPath)) {
            JUnitFormatter::format($junitPath);
        }

        return 0;
    }

    /**
     * Merge all coverage fragments from directory.
     */
    private static function mergeFragments(string $fragmentDir): ?CodeCoverage
    {
        $files = self::findFragmentFiles($fragmentDir);

        if ([] === $files) {
            fwrite(STDERR, "No coverage fragments found in $fragmentDir\n");

            return null;
        }

        $merged = null;

        foreach ($files as $file) {
            $cc = self::loadFragment($file);

            if (null === $cc) {
                continue;
            }

            $merged = self::mergeIntoCollection($merged, $cc);
        }

        return $merged;
    }

    /**
     * Find all fragment files in the directory.
     *
     * @return string[]
     */
    private static function findFragmentFiles(string $fragmentDir): array
    {
        $result = glob(rtrim($fragmentDir, '/') . '/*.phpser');

        return false !== $result ? $result : [];
    }

    /**
     * Merge a coverage instance into the collection.
     */
    private static function mergeIntoCollection(?CodeCoverage $merged, CodeCoverage $cc): CodeCoverage
    {
        if (null === $merged) {
            return $cc;
        }

        // Merge coverage data
        $merged->merge($cc);

        return $merged;
    }

    /**
     * Load a single coverage fragment from file.
     */
    private static function loadFragment(string $file): ?CodeCoverage
    {
        $data = @file_get_contents($file);

        if (false === $data || '' === $data) {
            return null;
        }

        $cc = @unserialize($data, ['allowed_classes' => true]);

        return $cc instanceof CodeCoverage ? $cc : null;
    }

    /**
     * Write merged coverage data in PHPUnit XML format.
     *
     * Uses a UUID as the test identifier to avoid XPath conflicts.
     */
    private static function writeCoverageXml(CodeCoverage $coverage, string $outDir): void
    {
        @mkdir($outDir, 0777, true);

        $phpUnitVersion = self::getPhpUnitVersion();
        $writer = new PhpUnitXmlFacade($phpUnitVersion);
        $writer->process($coverage, $outDir);
    }

    /**
     * Get the PHPUnit version for the XML writer.
     */
    private static function getPhpUnitVersion(): string
    {
        if (!class_exists('PHPUnit\\Runner\\Version')) {
            return 'unknown';
        }

        $versionClass = 'PHPUnit\\Runner\\Version';

        return $versionClass::id();
    }
}
