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

use function file_get_contents;
use function implode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Raneomik\InfectionTestFramework\Tester\Command\CommandLineBuilder;
use Raneomik\InfectionTestFramework\Tester\Command\CommandScriptBuilder;
use Raneomik\InfectionTestFramework\Tester\Command\InitialTestRunCommandBuilder;
use Raneomik\InfectionTestFramework\Tester\Config\MutationConfigBuilderFactory;
use Raneomik\InfectionTestFramework\Tester\TesterAdapter;
use Raneomik\InfectionTestFramework\Tester\VersionParser;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use function Raneomik\Tests\InfectionTestFramework\Tester\normalizePath as p;
use function realpath;
use ReflectionProperty;
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

    public function test_it_has_a_name(): void
    {
        $adapter = $this->createAdapter();
        self::assertSame('tester', $adapter->getName());
    }

    #[DataProvider('passProvider')]
    public function test_it_determines_whether_tests_pass_or_not(
        string $output,
        bool $expectedResult,
    ): void {
        $adapter = $this->createAdapter();
        $result = $adapter->testsPass($output);

        self::assertSame($expectedResult, $result);
    }

    /**
     * @return iterable<array{0: string, 1: bool}>
     */
    public static function passProvider(): iterable
    {
        yield ['OK, but incomplete, skipped, or risky tests!', true];

        yield ['OK (5 tests, 3 assertions)', true];

        yield ['WARNINGS!', true];

        yield ['warnings!', true];

        yield ['FAILURES!', false];

        yield ['failures!', false];

        yield ['ERRORS!', false];

        yield ['errors!', false];

        yield ['unhandled string', false];
    }

    public function test_it_does_nothing_on_skip_init(): void
    {
        $adapter = $this->createAdapter();
        $commandLine = $adapter->getInitialTestRunCommandLine('', [], true);

        self::assertEmpty($commandLine);
    }

    public function test_it_sets_initial_script(): void
    {
        $adapter = $this->createAdapter();
        $commandLine = $adapter->getInitialTestRunCommandLine('', [], false);

        self::assertContains($initScript = $this->tmp . '/run-initial-tester.php', $commandLine);
        self::assertFileExists($initScript);
    }

    public function test_it_adds_extra_options_for_mutant_command_line(): void
    {
        $adapter = $this->createAdapter();
        $commandLine = $adapter->getMutantCommandLine(
            [],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
            '--filter=xyz',
        );

        self::assertContains('--filter=xyz', $commandLine);
    }

    public function test_it_creates_interceptor_file(): void
    {
        $adapter = $this->createAdapter();

        $adapter->getMutantCommandLine(
            [],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
            '',
        );

        $expectedConfigPath = $this->tmp . '/bootstrap-mutant-a1b2c3.php';

        self::assertFileExists($expectedConfigPath);
    }

    public function test_adds_original_bootstrap_to_the_created_config_file_with_relative_path(): void
    {
        $adapter = $this->createAdapter();

        $adapter->getMutantCommandLine(
            [],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
            '',
        );

        self::assertStringContainsString(
            'tests/bootstrap.php',
            (string) @file_get_contents($this->tmp . '/bootstrap-mutant-a1b2c3.php'),
        );
    }

    public function test_it_has_junit_report(): void
    {
        $adapter = $this->createAdapter();

        self::assertTrue($adapter->hasJUnitReport(), 'Tester Framework must have JUnit report');
    }

    public function test_tester_name(): void
    {
        self::assertSame('tester', $this->createAdapter()->getName());
    }

    public function test_prepare_arguments_and_options_contains_run_first(): void
    {
        $adapter = $this->createAdapter();

        $commandLine = $adapter->getMutantCommandLine(
            [],
            self::MUTATED_FILE_PATH,
            self::MUTATION_HASH,
            self::ORIGINAL_FILE_PATH,
            '--skip blah',
        );

        self::assertStringContainsString(
            'path/to/tester --skip blah',
            implode(' ', $commandLine),
        );
    }

    private function createAdapter(?string $version = 'unknown'): TesterAdapter
    {
        $jUnitFilePath = 'path/to/junit';

        $filesystem = new Filesystem();

        // Create the mutation config builder factory (dependency of TesterAdapter)
        $mutationConfigBuilderFactory = new MutationConfigBuilderFactory(
            $this->tmp,
            $this->pathToProject,
        );

        // Create the initial test run command builder (dependency of TesterAdapter)
        $commandScriptBuilder = new CommandScriptBuilder(
            ['src'],
            $this->tmp,
            $this->pathToProject,
            Path::makeRelative($jUnitFilePath, $this->tmp),
            $filesystem,
            new CommandLineBuilder(),
        );

        $initialTestRunCommandBuilder = new InitialTestRunCommandBuilder(
            $commandScriptBuilder
        );

        $adapter = new TesterAdapter(
            'tester',
            '/path/to/tester',
            ['projectSrc/dir'],
            new Filesystem(),
            new VersionParser('Tester'),
            new CommandLineBuilder(),
            $initialTestRunCommandBuilder,
            $mutationConfigBuilderFactory->create(),
        );

        if (null !== $version) {
            $reflection = new ReflectionProperty($adapter, 'cachedVersion');
            $reflection->setValue($adapter, $version);
        }

        return $adapter;
    }
}
