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

namespace Raneomik\InfectionTestFramework\Tester\FrameworkRunner;

use function file_get_contents;
use function preg_match;
use Raneomik\InfectionTestFramework\Tester\Coverage\AssertionsCounter;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoveringTestIdentifier;
use Tester\Runner\OutputHandler;
use Tester\Runner\Test;

/**
 * @internal
 *
 * @see \Raneomik\Tests\InfectionTestFramework\Tester\FrameworkRunner\FailureOutputHandlerTest
 */
final readonly class FailureOutputHandler implements OutputHandler
{
    private AssertionsCounter $assertCounter;

    public function __construct(
        private CoveringTestIdentifier $testIdentifier = new CoveringTestIdentifier(),
    ) {
        $this->assertCounter = AssertionsCounter::getInstance();
    }

    public function begin(): void
    {
    }

    public function prepare(Test $test): void
    {
    }

    public function finish(Test $test): void
    {
        if (Test::Failed !== $test->getResult()) {
            return;
        }

        $testFile = $test->getFile();
        $methodName = $test->title ?? 'test';

        $content = @file_get_contents($testFile) ?: '';

        $namespace = '';

        if (1 === preg_match('/^\s*namespace\s+([a-zA-Z0-9_\\\\]+)\s*;/m', $content, $nm)) {
            $namespace = $nm[1];
        }

        $class = '';

        if (1 === preg_match('/\bclass\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $cm)) {
            $class = $cm[1];
        }

        $assertCounts = $this->assertCounter->countAssertions($testFile, $content);
        $assertCountInTest = $assertCounts[$methodName] ?? $assertCounts['total'] ?? 0;
        echo "\n" . 'Tests: 1, Assertions: ' . $assertCountInTest . "\n";

        echo $this->testIdentifier->getTestId(
            $methodName,
            $class,
            $namespace,
            $testFile,
        ) . "\n\n";
    }

    public function end(): void
    {
    }
}
