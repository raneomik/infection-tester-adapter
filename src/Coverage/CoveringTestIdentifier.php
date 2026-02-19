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
use function preg_match;
use function sprintf;
use function str_contains;
use function str_ends_with;

final readonly class CoveringTestIdentifier
{
    /**
     * @param string[] $includedFiles
     */
    public function __construct(
        private array $includedFiles,
    ) {
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
                $testId = $this->extractTestIdFromFile($includedFile);

                if (null !== $testId) {
                    return $testId;
                }
            }
        }

        return 'global-coverage';
    }

    /**
     * Extract test identifier from a test file path.
     */
    private function extractTestIdFromFile(string $filePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = @file_get_contents($filePath);

        if (false === $content) {
            return null;
        }

        $className = $this->extractClass($content);

        // TestCase format: extract method from --method=xxx in argv
        $methodName = $this->extractMethodFromArgv();

        if ('' !== $methodName) {
            return sprintf(
                '%s::%s',
                $className,
                $methodName,
            );
        }

        // PHPT procedural or test() function format
        // Use filename as identifier since there's no class
        $basename = basename($filePath, '.php');
        $basename = basename($basename, '.phpt');

        return sprintf(
            '%s::%s',
            '' !== $className ? $className . '\\' . $basename : $basename,
            'test',
        );
    }

    private function extractClass(string $testContent): string
    {
        // Extract namespace
        $namespace = '';

        if (0 < preg_match('/^\s*namespace\s+([a-zA-Z0-9_\\\\]+)\s*;/m', $testContent, $matches)) {
            $namespace = $matches[1];
        }

        // Extract class name
        $className = '';

        if (0 < preg_match('/^\s*(?:final\s+)?(?:abstract\s+)?class\s+([a-zA-Z0-9_]+)/m', $testContent, $matches)) {
            $className = $matches[1];
        }

        return match (true) {
            '' !== $className && '' !== $namespace => $namespace . '\\' . $className,
            '' !== $namespace => $namespace,
            default => '',
        };
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
