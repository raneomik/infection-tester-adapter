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

namespace Raneomik\Tests\InfectionTestFramework\Tester\Coverage;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageMerger;
use function Raneomik\Tests\InfectionTestFramework\Tester\coverageFragment;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use function Raneomik\Tests\InfectionTestFramework\Tester\fixtureFilesPath;
use RuntimeException;
use function serialize;
use function str_replace;
use Throwable;

#[Group('unit')]
final class CoverageMergerTest extends FileSystemTestCase
{
    #[TestWith([
        'error' => RuntimeException::class,
        'message' => '/No coverage fragments found/',
        'dir' => '/Fixtures/Files/tester',
    ], 'RuntimeException')]
    #[TestWith([
        'error' => RuntimeException::class,
        'message' => '/No coverage fragments found/',
        'dir' => 'not-existing-dir',
    ], 'missing directory')]
    public function test_it_outputs_error(
        string $error,
        string $message,
        string $dir,
    ): void {
        $merger = new CoverageMerger();

        /** @var class-string<Throwable> $error */
        self::expectException($error);
        self::expectExceptionMessageMatches($message);
        $merger->merge(
            dirname(__DIR__) . $dir,
            $this->tmp . '/coverage-xml',
        );
    }

    public function test_it_merges_fragments(): void
    {
        [$fragDir, $junit] = $this->writeFragment();

        $merger = new CoverageMerger();

        self::assertDirectoryDoesNotExist($this->tmp . '/coverage-xml');

        $out = $merger->merge($fragDir, $this->tmp . '/coverage-xml', $junit);

        self::assertDirectoryExists($this->tmp . '/coverage-xml');
        self::assertFileExists($this->tmp . '/coverage-xml/index.xml');
        self::assertFileExists($this->tmp . '/coverage-xml/PlainTest.php.xml');
        self::assertFileExists($this->tmp . '/coverage-xml/TestCaseTest.php.xml', 'every fragment is merged');
        self::assertSame(0, $out);
        self::assertStringContainsString('Nette Test Suite', (string) file_get_contents($junit), 'JUnit report is formatted');
    }

    public function test_it_merges_without_junit_report(): void
    {
        [$fragDir] = $this->writeFragment();

        self::assertSame(0, (new CoverageMerger())->merge($fragDir, $this->tmp . '/coverage-xml', $this->tmp . '/missing-junit.xml'));
        self::assertSame(0, (new CoverageMerger())->merge($fragDir, $this->tmp . '/coverage-xml'));
        self::assertFileDoesNotExist($this->tmp . '/missing-junit.xml');
    }

    /**
     * @return array<int, string>
     */
    private function writeFragment(): array
    {
        $fragDir = $this->tmp . '/fragments';

        mkdir($fragDir, 0755, true);

        file_put_contents(
            $fragDir . '/cc-fragment.phpser',
            coverageFragment(fixtureFilesPath('/tester/tests/PlainTest.php'), 5, 'PlainTest::test'),
        );

        file_put_contents(
            $fragDir . '/cc-other-fragment.phpser',
            coverageFragment(fixtureFilesPath('/tester/tests/TestCaseTest.php'), 14, \App\Tester\Tests\TestCaseTest::class . '::testFail'),
        );
        file_put_contents($fragDir . '/cc-corrupted.phpser', 'not a fragment');
        file_put_contents($fragDir . '/cc-not-coverage.phpser', serialize(['not' => 'coverage']));

        $junit = $this->tmp . '/plain-junit.xml';
        $junitDistContent = file_get_contents(fixtureFilesPath('/plain-junit.dist.xml')) ?: '';

        file_put_contents(
            $junit,
            str_replace('%root%', dirname(__DIR__, 3), $junitDistContent)
        );

        return [$fragDir, $junit];
    }
}
