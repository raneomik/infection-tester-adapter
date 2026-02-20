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
use function preg_match;
use function preg_match_all;
use const PREG_OFFSET_CAPTURE;
use function preg_quote;
use function strlen;
use function substr;

final readonly class AssertionsCounter
{
    /**
     * @return array<string, int>
     */
    public function countAssertions(string $fileContent): array
    {
        $assertions = [];

        foreach ($this->extractMethods($fileContent) as $method) {
            $assertions[$method] = $this->countAssertionsInMethod($fileContent, $method);
        }

        return $assertions + [
            'total' => $this->countAssertionsInContent($fileContent),
        ];
    }

    /**
     * @return string[]
     */
    private function extractMethods(string $testContent): array
    {
        if (0 < preg_match_all('/function\s+(\w+)\s*\(/', $testContent, $matches)) {
            return $matches[1];
        }

        return [];
    }

    private function countAssertionsInMethod(string $testContent, string $methodName): int
    {
        // Extrait le corps de la méthode
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

        while ($i < strlen($testContent) && 0 < $depth) {
            if ('{' === $testContent[$i]) {
                ++$depth;
            }

            if ('}' === $testContent[$i]) {
                --$depth;
            }

            ++$i;
        }

        $methodBody = substr($testContent, $start, $i - $start - 1);

        return $this->countAssertionsInContent($methodBody);
    }

    private function countAssertionsInContent(string $testContent): int
    {
        preg_match_all('/\bAssert::(.*)\s*\(/', $testContent, $found);

        return count($found[0]);
    }
}
