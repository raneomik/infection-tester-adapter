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

namespace Raneomik\Tests\InfectionTestFramework\Tester\Script;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use const PHP_BINARY;
use PHPUnit\Framework\Attributes\Group;
use Raneomik\InfectionTestFramework\Tester\Compatibility\TestSize;
use Raneomik\InfectionTestFramework\Tester\Compatibility\TestStatus;
use Raneomik\InfectionTestFramework\Tester\Script\InitialTestRunner;
use function Raneomik\Tests\InfectionTestFramework\Tester\codeCoverage;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use function Raneomik\Tests\InfectionTestFramework\Tester\fixtureFilesPath;
use function serialize;
use function sprintf;
use function str_replace;
use function var_export;

#[Group('integration')]
final class InitialTestRunnerTest extends FileSystemTestCase
{
    public function test_it_removes_fragments_of_a_previous_run(): void
    {
        mkdir($fragmentDir = $this->tmp . '/coverage-fragments');
        file_put_contents($stale = $fragmentDir . '/cc-1-stale.phpser', 'stale');

        $exitCode = InitialTestRunner::execute([PHP_BINARY, '-r', 'exit(0);'], $fragmentDir, $this->tmp, $this->tmp . '/junit.xml');

        self::assertFileDoesNotExist($stale);
        self::assertSame(1, $exitCode, 'passing tests without coverage must fail the initial run');
    }

    public function test_it_removes_fragments_once_merged(): void
    {
        mkdir($fragmentDir = $this->tmp . '/coverage-fragments');

        $cc = codeCoverage([__FILE__]);
        // @phpstan-ignore-next-line
        $cc->start(self::class . '::test_it_removes_fragments_once_merged', TestSize::small());
        // @phpstan-ignore-next-line
        $cc->stop(true, TestStatus::success());
        file_put_contents($valid = $this->tmp . '/valid.phpser', serialize($cc));

        $junit = $this->tmp . '/plain-junit.xml';
        file_put_contents($junit, str_replace('%root%', dirname(__DIR__, 3), file_get_contents(fixtureFilesPath('/plain-junit.dist.xml')) ?: ''));

        // the "test run" writes its fragment, as Tester processes do
        $copy = sprintf('copy(%s, %s);', var_export($valid, true), var_export($fragmentDir . '/cc-1-run.phpser', true));
        self::assertSame(0, InitialTestRunner::execute([PHP_BINARY, '-r', $copy], $fragmentDir, $this->tmp, $junit));

        self::assertFileExists($this->tmp . '/coverage-xml/index.xml');
        self::assertFileDoesNotExist($fragmentDir . '/cc-1-run.phpser');
    }
}
