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

namespace Raneomik\Tests\InfectionTestFramework\Tester\Command;

use function dirname;
use PHPUnit\Framework\Attributes\Group;
use Raneomik\InfectionTestFramework\Tester\Command\CommandLineBuilder;
use Raneomik\InfectionTestFramework\Tester\Command\CommandScriptBuilder;
use Raneomik\InfectionTestFramework\Tester\Coverage\PrependScriptGenerator;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use Symfony\Component\Filesystem\Filesystem;

#[Group('integration')]
final class CommandScriptBuilderTest extends FileSystemTestCase
{
    private CommandScriptBuilder $commandScriptBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandScriptBuilder = new CommandScriptBuilder(
            [
                'tester/',
                'not-existent',
            ],
            $this->tmp,
            dirname(__DIR__) . '/Fixtures/Files',
            'junit.xml',
            new Filesystem(),
            new CommandLineBuilder(),
            new PrependScriptGenerator(),
        );
    }

    public function test_it_builds_initial_script(): void
    {
        self::assertSame(
            $this->tmp . '/run-initial-tester.php',
            $initialScript = $this->commandScriptBuilder->buildInitialTestWrapper('tester'),
        );
        self::assertFileExists($initialScript);
        self::asserChmod('0755', $initialScript);
    }

    public function test_it_builds_setup_script(): void
    {
        self::assertSame($this->tmp . '/junit.xml', $this->commandScriptBuilder->getJUnitTmpPath());

        self::assertSame($this->tmp . '/tester-setup.php', $setupScript = $this->commandScriptBuilder->buildSetupScript());
        self::assertFileExists($setupScript);
        self::assertFileContains('/Fixtures/Files/tester', $setupScript);
        self::asserChmod('0755', $setupScript);

        self::assertDirectoryExists($this->tmp . '/coverage-fragments');
        self::asserChmod('0755', $this->tmp . '/coverage-fragments');

        self::assertFileExists($prependFile = $this->tmp . '/coverage_prepend.php');
        self::asserChmod('0644', $prependFile);
        self::assertFileContains('/vendor/autoload.php', $prependFile);
        self::assertFileContains('/Fixtures/Files/tester', $prependFile);
    }
}
