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

namespace Raneomik\Tests\InfectionTestFramework\Tester\Override;

use Infection\FileSystem\Locator\FileOrDirectoryNotFound;
use function mkdir;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Raneomik\InfectionTestFramework\Tester\Override\TesterConfigLocator;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use function touch;

#[Group('unit')]
final class TesterConfigLocatorTest extends FileSystemTestCase
{
    #[TestWith(['xml'])]
    #[TestWith(['yml'])]
    #[TestWith(['xml.dist'])]
    #[TestWith(['yml.dist'])]
    #[TestWith(['dist.xml'])]
    #[TestWith(['dist.yml'])]
    public function test_it_locates_config_file(string $extension): void
    {
        touch($config = $this->tmp . '/phpunit.' . $extension);

        self::assertSame($config, (new TesterConfigLocator($this->tmp))->locate('phpunit'));
    }

    public function test_it_prefers_the_first_extension_and_the_custom_dir(): void
    {
        mkdir($this->tmp . '/custom');
        touch($this->tmp . '/tester.xml');
        touch($this->tmp . '/custom/tester.yml.dist');
        touch($config = $this->tmp . '/custom/tester.yml');

        self::assertSame($config, (new TesterConfigLocator($this->tmp))->locate('tester', $this->tmp . '/custom'));
    }

    public function test_it_falls_back_to_tester_bootstrap(): void
    {
        mkdir($this->tmp . '/tests');
        touch($bootstrap = $this->tmp . '/tests/bootstrap.php');

        self::assertSame($bootstrap, (new TesterConfigLocator($this->tmp))->locate('tester'));
    }

    public function test_it_falls_back_for_tester_only(): void
    {
        mkdir($this->tmp . '/tests');
        touch($this->tmp . '/tests/bootstrap.php');

        $this->expectException(FileOrDirectoryNotFound::class);
        $this->expectExceptionMessage('"phpunit.xml", "phpunit.yml", "phpunit.xml.dist", "phpunit.yml.dist", "phpunit.dist.xml", "phpunit.dist.yml"');

        (new TesterConfigLocator($this->tmp))->locate('phpunit');
    }

    public function test_it_fails_without_tester_config_nor_bootstrap(): void
    {
        $this->expectException(FileOrDirectoryNotFound::class);
        $this->expectExceptionMessage('"tester.dist.yml", "' . $this->tmp . '/tests/bootstrap.php"');

        (new TesterConfigLocator($this->tmp))->locate('tester');
    }
}
