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

use function file_get_contents;
use function preg_match;
use function preg_match_all;
use const PREG_OFFSET_CAPTURE;
use function preg_quote;
use function strlen;
use function substr;

final class AssertionsCounter
{
    private static ?self $instance = null;

    /**
     * Cache: filePath → ['total' => int, methodName => int, ...]
     *
     * @var array<string, array<string, int>>
     */
    private static array $cache = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$cache = [];
    }

    /**
     * @return array<string, int>
     */
    public function countAssertions(string $filePath, ?string $fileContent = null): array
    {
        if (null !== $fileContent) {
            return self::$cache[$filePath] ??= $this->countInFileContent($fileContent);
        }

        return $this->countInFile($filePath);
    }

    public function countInMethod(string $filePath, string $methodName): int
    {
        return $this->countInFile($filePath)[$methodName] ?? 0;
    }

    /**
     * @return array<string, int>
     */
    private function countInFile(string $filePath): array
    {
        if (isset(self::$cache[$filePath])) {
            return self::$cache[$filePath];
        }

        $content = @file_get_contents($filePath);

        if (false === $content) {
            return self::$cache[$filePath] = ['total' => 0];
        }

        return self::$cache[$filePath] = $this->countInFileContent($content);
    }

    /**
     * @return array<string, int>
     */
    private function countInFileContent(string $content): array
    {
        $assertions = [];

        foreach ($this->extractMethods($content) as $method) {
            $assertions[$method] = $this->countAssertionsInMethod($content, $method);
        }

        return $assertions + ['total' => $this->countAssertionsInContent($content)];
    }

    /**
     * @return string[]
     */
    private function extractMethods(string $testContent): array
    {
        if (false !== preg_match_all('/function\s+(\w+)\s*\(/', $testContent, $matches)) {
            return $matches[1];
        }

        return [];
    }

    private function countAssertionsInMethod(string $testContent, string $methodName): int
    {
        if (1 !== preg_match(
            '/function\s+' . preg_quote($methodName, '/') . '\s*\([^)]*\)\s*(?::\s*\S+\s*)?\{/s',
            $testContent,
            $m,
            PREG_OFFSET_CAPTURE
        )) {
            return 0;
        }

        $i = $start = $m[0][1] + strlen($m[0][0]);
        $depth = 1;
        $len = strlen($testContent);

        while ($i++ < $len && 0 < $depth) {
            match ($testContent[$i]) {
                '{' => $depth++,
                '}' => $depth--,
                default => null,
            };
        }

        $methodBody = substr($testContent, $start, $i - $start);

        return $this->countAssertionsInContent($methodBody);
    }

    private function countAssertionsInContent(string $testContent): int
    {
        return (int) preg_match_all('/\bAssert\s*::\s*\w+\s*\(/', $testContent);
    }
}
