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
use function extension_loaded;
use function file_put_contents;
use function mkdir;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageMerger;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\PcovDriver;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Test\TestSize;
use SebastianBergmann\CodeCoverage\Test\TestStatus;
use function serialize;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Throwable;

#[Group('unit')]
final class CoverageMergerTest extends FileSystemTestCase
{
    public function test_it_merges_fragments(): void
    {
        $fragDir = $this->tmp . '/fragments';
        mkdir($fragDir, 0755, true);
        $this->writeFragment($fragDir);

        $merger = new CoverageMerger();

        self::assertDirectoryDoesNotExist($this->tmp . '/coverage-xml');

        $out = $merger->merge($fragDir, $this->tmp . '/coverage-xml');

        self::assertDirectoryExists($this->tmp . '/coverage-xml');
        self::assertSame(0, $out);
    }

    #[TestWith([
        'error' => RuntimeException::class,
        'message' => '/No coverage fragments found/',
        'dir' => '/Fixtures/Files/tester',
    ], 'RuntimeException')]
    #[TestWith([
        'error' => DirectoryNotFoundException::class,
        'message' => '/directory does not exist/',
        'dir' => 'not-existing-dir',
    ], 'DirectoryNotFoundException')]
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

    private function writeFragment(string $dir): void
    {
        $filter = new Filter();
        $filter->includeFiles([__FILE__]);

        $driver = extension_loaded('pcov') ? new PcovDriver($filter) : new XdebugDriver($filter);
        $cc = new CodeCoverage($driver, $filter);
        $cc->start('test::fixture', TestSize::Small);
        $cc->stop(true, TestStatus::Success);

        file_put_contents($dir . '/cc-fragment.phpser', serialize($cc));
    }
}
