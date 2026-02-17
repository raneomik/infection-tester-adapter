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

use function count;
use DOMDocument;
use DOMElement;
use function file_get_contents;
use function is_file;
use function number_format;
use function pathinfo;
use const PATHINFO_FILENAME;
use function preg_match;
use function str_contains;
use function str_replace;

/**
 * Formats JUnit XML from Tester format to PHPUnit-compatible format.
 *
 * Transforms Tester's flat structure:
 *   <testcase classname="/path/Test.php method=testMethod" name="..."/>
 *
 * Into PHPUnit's hierarchical structure:
 *   <testsuite name="Namespace\TestClass">
 *     <testcase name="testMethod" class="Namespace\TestClass" classname="Namespace\TestClass"/>
 *   </testsuite>
 */
final class JUnitFormatter
{
    /**
     * Format a JUnit XML file from Tester format to PHPUnit format.
     *
     * @param string $junitPath Path to the JUnit XML file to format
     *
     * @return bool True if formatting succeeded, false otherwise
     */
    public static function format(string $junitPath): bool
    {
        $content = @file_get_contents($junitPath);

        if (false === $content || !str_contains($content, '<testcase')) {
            return false;
        }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (false === @$dom->loadXML($content)) {
            return false;
        }

        $testcases = self::extractTestcases($dom);

        if ([] === $testcases) {
            return false;
        }

        $groupedTestcases = self::groupTestcasesByClass($testcases);
        $newDom = self::buildPhpUnitStructure($groupedTestcases);

        return false !== $newDom->save($junitPath);
    }

    /**
     * Extract all testcase elements from the document.
     *
     * @return array<int, array{element: DOMElement, parsed: array{file: string, method: string, class: string, namespace: string}}>
     */
    private static function extractTestcases(DOMDocument $dom): array
    {
        $testcases = [];
        $testcaseElements = $dom->getElementsByTagName('testcase');

        foreach ($testcaseElements as $testcase) {
            $classname = $testcase->getAttribute('classname');
            $name = $testcase->getAttribute('name');

            // Parse Tester format: "/path/Test.php method=testMethod"
            $parsed = self::parseTesterFormat('' !== $classname ? $classname : $name);

            if (null !== $parsed) {
                $testcases[] = [
                    'element' => $testcase,
                    'parsed' => $parsed,
                ];
            }
        }

        return $testcases;
    }

    /**
     * Parse Tester testcase format.
     *
     * @return array{file: string, method: string, class: string, namespace: string}|null
     */
    private static function parseTesterFormat(string $attribute): ?array
    {
        // Pattern 1: "/path/to/Test.php method=testMethod" (TestCase)
        if (0 < preg_match('#^(.+\.php)\s+method=(\w+)$#', $attribute, $matches)) {
            $filePath = $matches[1];
            $method = $matches[2];
        }
        // Pattern 2: "/path/to/Test.php" (procédural ou test() function)
        elseif (0 < preg_match('#^(.+\.php)$#', $attribute, $matches)) {
            $filePath = $matches[1];
            $method = 'test'; // Méthode synthétique pour tests procéduraux
        } else {
            return null;
        }

        // Extract class and namespace from file
        $classInfo = self::extractClassInfo($filePath);

        return [
            'file' => $filePath,
            'method' => $method,
            'class' => $classInfo['class'],
            'namespace' => $classInfo['namespace'],
        ];
    }

    /**
     * Extract class name and namespace from a PHP file.
     *
     * @return array{class: string, namespace: string}
     */
    private static function extractClassInfo(string $filePath): array
    {
        $default = [
            'class' => pathinfo($filePath, PATHINFO_FILENAME),
            'namespace' => '',
        ];

        if (!is_file($filePath)) {
            return $default;
        }

        $content = @file_get_contents($filePath);

        if (false === $content) {
            return $default;
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

        if ('' === $className) {
            $className = pathinfo($filePath, PATHINFO_FILENAME);
        }

        return [
            'class' => $className,
            'namespace' => $namespace,
        ];
    }

    /**
     * Group testcases by their class (full namespace + class name).
     *
     * @param array<int, array{element: DOMElement, parsed: array{file: string, method: string, class: string, namespace: string}}> $testcases
     *
     * @return array<string, array{namespace: string, class: string, file: string, tests: array<int, array{element: DOMElement, method: string}>}>
     */
    private static function groupTestcasesByClass(array $testcases): array
    {
        $grouped = [];

        foreach ($testcases as $testcase) {
            $parsed = $testcase['parsed'];
            $fullClass = '' !== $parsed['namespace']
                ? $parsed['namespace'] . '\\' . $parsed['class']
                : $parsed['class'];

            if (!isset($grouped[$fullClass])) {
                $grouped[$fullClass] = [
                    'namespace' => $parsed['namespace'],
                    'class' => $parsed['class'],
                    'file' => $parsed['file'],
                    'tests' => [],
                ];
            }

            $grouped[$fullClass]['tests'][] = [
                'element' => $testcase['element'],
                'method' => $parsed['method'],
            ];
        }

        return $grouped;
    }

    /**
     * Build PHPUnit-style hierarchical structure.
     *
     * @param array<string, array{namespace: string, class: string, file: string, tests: array<int, array{element: DOMElement, method: string}>}> $groupedTestcases
     */
    private static function buildPhpUnitStructure(array $groupedTestcases): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        // Root testsuites element
        $rootTestsuites = $dom->createElement('testsuites');
        $dom->appendChild($rootTestsuites);

        // Main testsuite (CLI Arguments equivalent)
        $mainTestsuite = $dom->createElement('testsuite');
        $mainTestsuite->setAttribute('name', 'Nette Test Suite');

        $totalTests = 0;
        $totalTime = 0.0;

        foreach ($groupedTestcases as $fullClass => $classData) {
            $totalTests += count($classData['tests']);
        }

        $mainTestsuite->setAttribute('tests', (string) $totalTests);
        $mainTestsuite->setAttribute('assertions', (string) $totalTests);
        $mainTestsuite->setAttribute('errors', '0');
        $mainTestsuite->setAttribute('failures', '0');
        $mainTestsuite->setAttribute('skipped', '0');

        $rootTestsuites->appendChild($mainTestsuite);

        // Create testsuite for each class
        foreach ($groupedTestcases as $fullClass => $classData) {
            $classTestsuite = $dom->createElement('testsuite');
            $classTestsuite->setAttribute('name', $fullClass);

            // Use absolute path like PHPUnit does
            $classTestsuite->setAttribute('file', $classData['file']);

            $classTestsuite->setAttribute('tests', (string) count($classData['tests']));
            $classTestsuite->setAttribute('assertions', (string) count($classData['tests']));
            $classTestsuite->setAttribute('errors', '0');
            $classTestsuite->setAttribute('failures', '0');
            $classTestsuite->setAttribute('skipped', '0');

            $suiteTime = 0.0;

            // Add testcases
            foreach ($classData['tests'] as $test) {
                $oldTestcase = $test['element'];
                $newTestcase = $dom->createElement('testcase');

                // PHPUnit format attributes - use absolute paths and dots in classname
                $newTestcase->setAttribute('name', $test['method']);
                $newTestcase->setAttribute('file', $classData['file']);
                $newTestcase->setAttribute('class', $fullClass);
                $newTestcase->setAttribute('classname', str_replace('\\', '.', $fullClass)); // Convert backslashes to dots
                $newTestcase->setAttribute('assertions', '1');

                // Preserve time if available
                $time = $oldTestcase->getAttribute('time');

                if ('' !== $time) {
                    $newTestcase->setAttribute('time', $time);
                    $suiteTime += (float) $time;
                } else {
                    $newTestcase->setAttribute('time', '0.001');
                    $suiteTime += 0.001;
                }

                // Preserve line if available
                $line = $oldTestcase->getAttribute('line');

                if ('' !== $line) {
                    $newTestcase->setAttribute('line', $line);
                }

                $classTestsuite->appendChild($newTestcase);
            }

            $classTestsuite->setAttribute('time', number_format($suiteTime, 6, '.', ''));
            $totalTime += $suiteTime;

            $mainTestsuite->appendChild($classTestsuite);
        }

        $mainTestsuite->setAttribute('time', number_format($totalTime, 6, '.', ''));

        return $dom;
    }
}
