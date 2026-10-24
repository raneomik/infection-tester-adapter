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

use function basename;
use function file_exists;
use function file_get_contents;
use function in_array;
use function pathinfo;
use const PATHINFO_FILENAME;
use function preg_match;
use Raneomik\InfectionTestFramework\Tester\Compatibility\TestSize;
use function sprintf;
use function str_contains;
use function str_ends_with;

/**
 * @see \Raneomik\Tests\InfectionTestFramework\Tester\Coverage\CoveringTestIdentifierTest
 */
final class CoveringTestIdentifier
{
    public const string NOT_TO_COVER = 'not-to-cover';

    private string $foundFilePath = '';

    private string $foundMethodName = '';

    /**
     * @param string[] $includedFiles
     */
    public function __construct(
        private readonly array $includedFiles = [],
    ) {
    }

    /**
     * Determine test size based on assertion count in the current test method.
     * Delegates to AssertionsCounter (cached per file).
     * <= 3  → small / <= 10 → medium / > 10  → large
     */
    public function identifySize(): object
    {
        if ('' === $this->foundFilePath || '' === $this->foundMethodName) {
            return TestSize::unknown();
        }

        $count = AssertionsCounter::countInMethod($this->foundFilePath, $this->foundMethodName);

        if (3 >= $count) {
            return TestSize::small();
        }

        if (10 >= $count) {
            return TestSize::medium();
        }

        return TestSize::large();
    }

    /**
     * Detect test identifier from included files.
     * This is the most reliable way since the test file is always included.
     *
     * @return non-empty-string
     */
    public function identifyTest(): string
    {
        foreach ($this->includedFiles as $includedFile) {
            if (str_contains($includedFile, '/vendor/')) {
                continue;
            }

            if (str_ends_with($includedFile, 'Test.php') || str_ends_with($includedFile, 'test.php')) {
                $testId = $this->extractTestId($includedFile);

                if (null !== $testId) {
                    return $testId;
                }
            }
        }

        return self::NOT_TO_COVER;
    }

    /**
     * The one rule naming a test file's class: coverage and JUnit ids must agree, or Infection aborts with "test not found".
     *
     * @return array{namespace: string, class: string}
     */
    public static function classOf(string $filePath, string $content): array
    {
        $namespace = 1 === preg_match('/^\s*namespace\s+([a-zA-Z0-9_\\\\]+)\s*;/m', $content, $matches) ? $matches[1] : '';

        // line-anchored: skips "class Foo" in comments, "new class extends" and "Foo::class"
        $class = 1 === preg_match('/^\s*(?:(?:final|abstract|readonly)\s+)*class\s+([a-zA-Z_][a-zA-Z0-9_]*)/m', $content, $matches)
            ? $matches[1]
            : pathinfo($filePath, PATHINFO_FILENAME);

        return ['namespace' => $namespace, 'class' => $class];
    }

    /**
     * @return non-empty-string
     */
    public function getTestId(
        string $methodName,
        string $className,
        string $namespace,
        string $filePath,
    ): string {
        $basename = basename($filePath, '.php');
        $basename = basename($basename, '.phpt');

        $classNamespace = $namespace . '\\' . $className;

        if (
            false === in_array('', [$methodName, $className, $namespace], true)
        ) {
            return sprintf(
                '%s::%s',
                $classNamespace,
                $methodName,
            );
        }

        if (
            '' !== $className
            && '' !== $namespace
        ) {
            return sprintf(
                '%s::%s',
                $classNamespace,
                'test',
            );
        }

        if ('' !== $namespace) {
            return sprintf(
                '%s::%s',
                $namespace . '\\' . $basename,
                'test',
            );
        }

        return sprintf('%s::%s', $basename, 'test');
    }

    /**
     * Extract test identifier from a test file path.
     *
     * @return non-empty-string|null
     */
    private function extractTestId(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = @file_get_contents($filePath);

        if (false === $content) {
            return null;
        }

        // cache-warmer for file
        AssertionsCounter::countAssertions($filePath, $content);

        // save for identifySize()
        $this->foundFilePath = $filePath;

        ['namespace' => $namespace, 'class' => $className] = self::classOf($filePath, $content);

        $methodName = $this->extractMethodFromArgv();

        // save for identifySize()
        $this->foundMethodName = $methodName;

        return $this->getTestId(
            $methodName,
            $className,
            $namespace,
            $filePath,
        );
    }

    /**
     * Extract method name from argv arguments.
     * Supports: --method=methodName format.
     */
    private function extractMethodFromArgv(): string
    {
        /** @var string $arg */
        foreach ($_SERVER['argv'] ?? [] as $arg) {// @phpstan-ignore-line
            if (0 < preg_match('/^--method=(\w+)$/', $arg, $matches)) {
                return $matches[1];
            }
        }

        return '';
    }
}
