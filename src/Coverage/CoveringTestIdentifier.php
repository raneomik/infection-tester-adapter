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
use function preg_match;
use SebastianBergmann\CodeCoverage\Test\TestSize\TestSize;
use function sprintf;
use function str_contains;
use function str_ends_with;

final class CoveringTestIdentifier
{
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
     * Delegates to AssertionsCounter (singleton + cache).
     * <= 3  → small / <= 10 → medium / > 10  → large
     */
    public function identifySize(): TestSize
    {
        if ('' === $this->foundFilePath || '' === $this->foundMethodName) {
            return TestSize::unknown(); // unknown
        }

        $count = AssertionsCounter::getInstance()->countInMethod($this->foundFilePath, $this->foundMethodName);

        if (3 >= $count) {
            return TestSize::small(); // small
        }

        if (10 >= $count) {
            return TestSize::medium(); // medium
        }

        return TestSize::large(); // large
    }

    /**
     * Detect test identifier from included files.
     * This is the most reliable way since the test file is always included.
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

        return 'not-to-cover';
    }

    public function getTestId(
        string $methodName,
        string $className,
        string $namespace,
        string $filePath,
    ): string {
        $basename = basename($filePath, '.php');
        $basename = basename($basename, '.phpt');

        if (
            !in_array('', [$methodName, $className, $namespace], true)
        ) {
            return sprintf(
                '%s::%s',
                $namespace . '\\' . $className,
                $methodName,
            );
        }

        if (
            '' !== $className
            && '' !== $namespace
        ) {
            return sprintf(
                '%s::%s',
                $namespace . '\\' . $className,
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
        AssertionsCounter::getInstance()->countAssertions($filePath, $content);

        // save for identifySize()
        $this->foundFilePath = $filePath;

        $namespace = '';

        if (0 < preg_match('/^\s*namespace\s+([a-zA-Z0-9_\\\\]+)\s*;/m', $content, $matches)) {
            $namespace = $matches[1];
        }

        $className = '';

        if (0 < preg_match('/\bclass\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $matches)) {
            $className = $matches[1];
        }

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
