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

/*
 * This file is part of the box project.
 *
 * (c) Kevin Herrera <kevin@herrera.io>
 *     Théo Fidry <theo.fidry@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Raneomik\Tests\InfectionTestFramework\Tester\Coverage;

use function dirname;
use PHPUnit\Framework\Attributes\Group;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageMerger;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use RuntimeException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

#[Group('unit')]
final class CoverageMergerTest extends FileSystemTestCase
{
    public function test_it_merges_fragments(): void
    {
        $merger = new CoverageMerger();

        self::assertDirectoryDoesNotExist($this->tmp . '/coverage-xml');

        $out = $merger->merge(
            dirname(__DIR__) . '/Fixtures/Files/fragments',
            $this->tmp . '/coverage-xml',
        );

        self::assertDirectoryExists($this->tmp . '/coverage-xml');
        self::assertSame(0, $out);
    }

    public function test_it_outputs_error(): void
    {
        $merger = new CoverageMerger();

        self::expectException(RuntimeException::class);
        self::expectExceptionMessageMatches('/No coverage fragments found/');
        $merger->merge(
            dirname(__DIR__) . '/Fixtures/Files/tester',
            $this->tmp . '/coverage-xml',
        );
    }

    public function test_it_(): void
    {
        $merger = new CoverageMerger();

        self::expectException(DirectoryNotFoundException::class);
        self::expectExceptionMessageMatches('/directory does not exist/');
        $merger->merge(
            'not-existing-dir',
            $this->tmp . '/coverage-xml',
        );
    }
}
