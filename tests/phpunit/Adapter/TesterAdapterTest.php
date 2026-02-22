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

namespace Raneomik\Tests\InfectionTestFramework\Tester\Adapter;

use function dirname;
use function extension_loaded;
use function file_get_contents;
use Infection\AbstractTestFramework\Coverage\TestLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Raneomik\InfectionTestFramework\Tester\Command\CommandLineBuilder;
use Raneomik\InfectionTestFramework\Tester\Command\CommandScriptBuilder;
use Raneomik\InfectionTestFramework\Tester\Command\InitialTestRunCommandBuilder;
use Raneomik\InfectionTestFramework\Tester\Config\MutationConfigBuilder;
use Raneomik\InfectionTestFramework\Tester\Coverage\PrependScriptGenerator;
use Raneomik\InfectionTestFramework\Tester\TapTestChecker;
use Raneomik\InfectionTestFramework\Tester\TesterAdapter;
use Raneomik\InfectionTestFramework\Tester\VersionParser;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use function Raneomik\Tests\InfectionTestFramework\Tester\normalizePath as p;
use function realpath;
use ReflectionProperty;
use function sprintf;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[Group('integration')]
final class TesterAdapterTest extends FileSystemTestCase
{
    private const MUTATION_HASH = 'a1b2c3';

    private const ORIGINAL_FILE_PATH = '/original/file/path';

    private const MUTATED_FILE_PATH = '/mutated/file/path';

    private string $pathToProject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pathToProject = p((string) realpath(__DIR__ . '/../Fixtures/Files/tester'));
    }

    /**
     * @return iterable<array{0: string, 1: bool}>
     */
    public static function passProvider(): iterable
    {
        yield ['1..1', true];

        yield ['1..5', true];

        yield ['5..5', true];

        yield ['5..1', true];

        yield ['a..b', false];

        yield ['not ok', false];

        yield ['bail out', false];

        yield ['unhandled string', false];
    }

    #[DataProvider('passProvider')]
    public function test_it_determines_whether_tests_pass_or_not(
        string $output,
        bool $expectedResult,
    ): void {
        $testerAdapter = $this->createAdapter();
        $result = $testerAdapter->testsPass($output);

        self::assertSame($expectedResult, $result);
    }

    public function test_it_has_a_name(): void
    {
        $testerAdapter = $this->createAdapter();
        self::assertSame('tester', $testerAdapter->getName());
    }

    public function test_it_shows_version(): void
    {
        $testerAdapter = $this->createAdapter(null);
        self::assertSame('2.6.0', $testerAdapter->getVersion());
        self::assertSame('2.6.0', $testerAdapter->getVersion(), 'cached version');
    }

    public function test_it_shows_recommendation(): void
    {
        $testerAdapter = $this->createAdapter('test');
        self::assertSame(
            'Check the executed command to identify the problem: test',
            $testerAdapter->getInitialTestsFailRecommendations('test'),
        );
    }

    public function test_it_does_nothing_on_skip_init(): void
    {
        $testerAdapter = $this->createAdapter();
        $commandLine = $testerAdapter->getInitialTestRunCommandLine('', [], true);

        self::assertEmpty($commandLine);
    }

    public function test_it_sets_initial_script(): void
    {
        $testerAdapter = $this->createAdapter();
        $commandLine = $testerAdapter->getInitialTestRunCommandLine('blabla', [], false);

        self::assertContains($initScript = $this->tmp . '/run-initial-tester.php', $commandLine);
        self::assertFileContains('blabla', $initScript);
        self::assertFileExists($initScript);
    }

    public function test_mutant_cmd_line(): string
    {
        $testerAdapter = $this->createAdapter();

        $testerAdapter->getMutantCommandLine(
            [
                new TestLocation('test', '/path/to/test', null),
                new TestLocation('anotherTest', '/path/to/another-test', null),
                new TestLocation('duplicate', '/path/to/another-test', null),
            ],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
        );

        $expectedBootstrapPath = $this->tmp . sprintf('/mutant/bootstrap-%s.php', self::MUTATION_HASH);
        $expectedWrapperPath = $this->tmp . sprintf('/mutant/wrapper-%s.php', self::MUTATION_HASH);

        foreach ([$expectedBootstrapPath, $expectedWrapperPath] as $createdFilePath) {
            self::assertFileExists($createdFilePath);
            self::assertFileContains(dirname(__DIR__, 3) . '/vendor/autoload.php', $createdFilePath);
        }

        self::assertFileContains(self::ORIGINAL_FILE_PATH, $expectedBootstrapPath);
        self::assertFileContains(self::MUTATED_FILE_PATH, $expectedBootstrapPath);

        self::assertFileContains('-j', $expectedWrapperPath);
        self::assertFileContains('1', $expectedWrapperPath);
        self::assertFileContains('-o', $expectedWrapperPath);
        self::assertFileContains('tap', $expectedWrapperPath);
        self::assertFileContains('/path/to/test', $expectedWrapperPath);
        self::assertFileContainsCount(1, '/path/to/another-test', $expectedWrapperPath);

        return @file_get_contents($expectedWrapperPath) ?: '';
    }

    #[RequiresPhpExtension('pcov')]
    #[Depends('test_mutant_cmd_line')]
    public function test_pcov_ini_options(string $inlineCmd): void
    {
        if (!extension_loaded('pcov')) {
            self::markTestSkipped('PCOV extension is not loaded.');
        }

        self::assertStringContainsString('pcov.enabled=1', $inlineCmd);
        self::assertStringContainsString('pcov.directory', $inlineCmd);
    }

    #[RequiresPhpExtension('xdebug')]
    #[Depends('test_mutant_cmd_line')]
    public function test_xdebug_ini_options(string $inlineCmd): void
    {
        if (extension_loaded('pcov')) {
            self::markTestSkipped('running first in PCOV, even if xdebug loaded');
        }

        self::assertStringContainsString('xdebug.start_with_request=yes', $inlineCmd);
        self::assertStringContainsString('xdebug.mode=coverage', $inlineCmd);
    }

    public function test_it_has_junit_report(): void
    {
        $testerAdapter = $this->createAdapter();

        self::assertTrue($testerAdapter->hasJUnitReport(), 'Tester Framework must have JUnit report');
    }

    private function createAdapter(?string $version = 'unknown'): TesterAdapter
    {
        $jUnitFilePath = 'path/to/junit';

        $filesystem = new Filesystem();

        $commandScriptBuilder = new CommandScriptBuilder(
            ['src'],
            $this->tmp,
            $this->pathToProject,
            Path::makeRelative($jUnitFilePath, $this->tmp),
            $filesystem,
            new CommandLineBuilder(),
            new PrependScriptGenerator(),
        );

        $initialTestRunCommandBuilder = new InitialTestRunCommandBuilder(
            $commandScriptBuilder
        );

        $testerAdapter = new TesterAdapter(
            'tester',
            'vendor/bin/tester',
            ['projectSrc/dir'],
            new VersionParser('Tester'),
            new CommandLineBuilder(),
            $initialTestRunCommandBuilder,
            new MutationConfigBuilder(
                $this->tmp,
                $this->pathToProject,
            ),
            new TapTestChecker(),
        );

        if (null !== $version) {
            $reflectionProperty = new ReflectionProperty($testerAdapter, 'cachedVersion');
            $reflectionProperty->setValue($testerAdapter, $version);
        }

        return $testerAdapter;
    }
}
