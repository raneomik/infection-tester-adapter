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
 *   <testsuite name="Namespace\TestClass" assertions="2" tests="1" time="0.001">
 *     <testcase name="testMethod" class="Namespace\TestClass" classname="Namespace\TestClass" assertions="2" time="0.001"/>
 *   </testsuite>
 */
final class JUnitFormatter
{
    /**
     * Cache for extracted class info to avoid re-reading same files.
     * Key: file path, Value: [class, namespace]
     *
     * @var array<string, array{class: string, namespace: string, assertions: array<string, int>}>
     */
    private static array $classInfoCache = [];

    private function __construct(
        private readonly string $junitPath,
        private readonly string $outputPath,
        private readonly AssertionsCounter $assertsCounter,
    ) {
        // Clear cache at the start of each format operation
        self::$classInfoCache = [];
    }

    /**
     * Format a JUnit XML file from Tester format to PHPUnit format.
     *
     * @param string $junitPath Path to the JUnit XML file to format
     *
     * @return bool True if formatting succeeded, false otherwise
     */
    public static function format(string $junitPath, ?string $outputPath = null): bool
    {
        $self = new self(
            $junitPath,
            $outputPath ?? $junitPath,
            new AssertionsCounter(),
        );

        return $self->doFormat();
    }

    private function doFormat(): bool
    {
        $content = @file_get_contents($this->junitPath);

        if (
            false === $content
            || !str_contains($content, '<testcase')) {
            return false;
        }

        $domDocument = new DOMDocument();
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = true;

        if (false === @$domDocument->loadXML($content)) {
            return false;
        }

        $testcases = $this->extractTestcases($domDocument);

        if ([] === $testcases) {
            return false;
        }

        $groupedTestcases = $this->groupTestcasesByClass($testcases);
        $newDom = $this->buildPhpUnitStructure($groupedTestcases);

        return false !== $newDom->save($this->outputPath);
    }

    /**
     * Extract all testcase elements from the document.
     *
     * @return array<int, array{
     *     element: DOMElement,
     *     parsed: array{
     *         file: string,
     *         method: string,
     *         class: string,
     *         namespace: string,
     *         assertions: array<string, int>,
     *     }
     * }>
     */
    private function extractTestcases(DOMDocument $domDocument): array
    {
        $testcases = [];
        $domNodeList = $domDocument->getElementsByTagName('testcase');

        foreach ($domNodeList as $testcase) {
            $classname = $testcase->getAttribute('classname');
            $name = $testcase->getAttribute('name');

            // Parse Tester format: "/path/Test.php method=testMethod"
            $parsed = $this->parseTesterFormat('' !== $classname ? $classname : $name);

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
     * @return array{file: string, method: string, class: string, namespace: string, assertions: array<string, int>}|null
     */
    private function parseTesterFormat(string $attribute): ?array
    {
        if (0 < preg_match('#^(.+\.php)\s+method=(\w+)$#', $attribute, $matches)) {
            $filePath = $matches[1];
            $method = $matches[2];
        } elseif (0 < preg_match('#^(.+\.php)$#', $attribute, $matches)) {
            $filePath = $matches[1];
            $method = 'test';
        } else {
            return null;
        }

        // Extract class and namespace from file
        $classInfo = $this->extractClassInfo($filePath);

        return [
            'file' => $filePath,
            'method' => $method,
            'class' => $classInfo['class'],
            'namespace' => $classInfo['namespace'],
            'assertions' => $classInfo['assertions'],
        ];
    }

    /**
     * Extract class name and namespace from a PHP file.
     *
     * @return array{class: string, namespace: string, assertions: array<string, int>}
     */
    private function extractClassInfo(string $filePath): array
    {
        // Check cache first - avoid re-reading same file multiple times
        // (TestCase with multiple methods triggers this 7× for same file = 3× slowdown)
        if (isset(self::$classInfoCache[$filePath])) {
            return self::$classInfoCache[$filePath];
        }

        $default = [
            'class' => pathinfo($filePath, PATHINFO_FILENAME),
            'namespace' => '',
            'assertions' => ['total' => 0],
        ];

        if (!is_file($filePath)) {
            return self::$classInfoCache[$filePath] = $default;
        }

        $content = @file_get_contents($filePath);

        if (false === $content) {
            return self::$classInfoCache[$filePath] = $default;
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

        return self::$classInfoCache[$filePath] = [
            'class' => $className,
            'namespace' => $namespace,
            'assertions' => $this->assertsCounter->countAssertions($content),
        ];
    }

    /**
     * Group testcases by their class (full namespace + class name).
     *
     * @param array<int, array{element: DOMElement, parsed: array{file: string, method: string, class: string, namespace: string, assertions: array<string, int>}}> $testcases
     *
     * @return array<string, array{namespace: string, class: string, file: string, assertions: int, tests: array<int, array{element: DOMElement, method: string, assertions: int}>}>
     */
    private function groupTestcasesByClass(array $testcases): array
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
                    'assertions' => $parsed['assertions']['total'],
                    'tests' => [],
                ];
            }

            $grouped[$fullClass]['tests'][] = [
                'element' => $testcase['element'],
                'method' => $method = $parsed['method'],
                'assertions' => $parsed['assertions'][$method] ?? 0,
            ];
        }

        return $grouped;
    }

    /**
     * Build PHPUnit-style hierarchical structure.
     *
     * @param array<string, array{namespace: string, class: string, file: string, assertions: int, tests: array<int, array{element: DOMElement, method: string, assertions: int}>}> $groupedTestcases
     */
    private function buildPhpUnitStructure(array $groupedTestcases): DOMDocument
    {
        $domDocument = new DOMDocument('1.0', 'UTF-8');
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = true;

        // Root testsuites element
        $rootTestsuites = $domDocument->createElement('testsuites');
        $domDocument->appendChild($rootTestsuites);

        // Main testsuite (CLI Arguments equivalent)
        $mainTestsuite = $domDocument->createElement('testsuite');
        $mainTestsuite->setAttribute('name', 'Nette Test Suite');

        // Accumulate real statistics from testcases
        $totalTests = 0;
        $totalAsserts = 0;
        $totalErrors = 0;
        $totalFailures = 0;
        $totalSkipped = 0;
        $totalTime = 0.0;

        foreach ($groupedTestcases as $fullClass => $classData) {
            $totalTests += count($classData['tests']);

            // Count real errors/failures/skipped from testcases
            foreach ($classData['tests'] as $test) {
                $element = $test['element'];

                // Check for errors/failures/skipped
                if (0 < $element->getElementsByTagName('error')->length) {
                    ++$totalErrors;
                }

                if (0 < $element->getElementsByTagName('failure')->length) {
                    ++$totalFailures;
                }

                if (0 < $element->getElementsByTagName('skipped')->length) {
                    ++$totalSkipped;
                }
            }

            if (0 < ($asserts = $classData['assertions'])) {
                $totalAsserts += $asserts;
            }
        }

        $mainTestsuite->setAttribute('tests', (string) $totalTests);
        $mainTestsuite->setAttribute('errors', (string) $totalErrors);
        $mainTestsuite->setAttribute('failures', (string) $totalFailures);
        $mainTestsuite->setAttribute('skipped', (string) $totalSkipped);
        $mainTestsuite->setAttribute('assertions', (string) $totalAsserts);

        $rootTestsuites->appendChild($mainTestsuite);

        // Create testsuite for each class
        foreach ($groupedTestcases as $fullClass => $classData) {
            $classTestsuite = $domDocument->createElement('testsuite');
            $classTestsuite->setAttribute('name', $fullClass);

            // Use absolute path like PHPUnit does
            $classTestsuite->setAttribute('file', $classData['file']);
            $classTestsuite->setAttribute('assertions', (string) $classData['assertions']);

            // Count real statistics for this class
            $classTests = count($classData['tests']);
            $classErrors = 0;
            $classFailures = 0;
            $classSkipped = 0;
            $classTime = 0.0;

            // Add testcases
            foreach ($classData['tests'] as $test) {
                $oldTestcase = $test['element'];
                $newTestcase = $domDocument->createElement('testcase');

                // PHPUnit format attributes - use absolute paths and dots in classname
                $newTestcase->setAttribute('name', $test['method']);
                $newTestcase->setAttribute('file', $classData['file']);
                $newTestcase->setAttribute('class', $fullClass);
                $newTestcase->setAttribute('classname', str_replace('\\', '.', $fullClass)); // Convert backslashes to dots
                $newTestcase->setAttribute('assertions', (string) $test['assertions']);

                // Preserve time if available
                $time = $oldTestcase->getAttribute('time');

                if ('' !== $time) {
                    $newTestcase->setAttribute('time', $time);
                    $classTime += (float) $time;
                } else {
                    $newTestcase->setAttribute('time', '0.001');
                    $classTime += 0.001;
                }

                // Preserve line if available
                $line = $oldTestcase->getAttribute('line');

                if ('' !== $line) {
                    $newTestcase->setAttribute('line', $line);
                }

                // Copy error/failure/skipped elements
                foreach (['error', 'failure', 'skipped'] as $childType) {
                    $children = $oldTestcase->getElementsByTagName($childType);

                    foreach ($children as $child) {
                        $newChild = $domDocument->importNode($child, true);
                        $newTestcase->appendChild($newChild);

                        // Count them
                        if ('error' === $childType) {
                            ++$classErrors;
                        } elseif ('failure' === $childType) {
                            ++$classFailures;
                        } elseif ('skipped' === $childType) {
                            ++$classSkipped;
                        }
                    }
                }

                $classTestsuite->appendChild($newTestcase);
            }

            $classTestsuite->setAttribute('tests', (string) $classTests);
            $classTestsuite->setAttribute('errors', (string) $classErrors);
            $classTestsuite->setAttribute('failures', (string) $classFailures);
            $classTestsuite->setAttribute('skipped', (string) $classSkipped);
            $classTestsuite->setAttribute('time', number_format($classTime, 6, '.', ''));

            $totalTime += $classTime;

            $mainTestsuite->appendChild($classTestsuite);
        }

        $mainTestsuite->setAttribute('time', number_format($totalTime, 6, '.', ''));

        return $domDocument;
    }
}
