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

namespace Raneomik\InfectionTestFramework\Tester;

use Infection\AbstractTestFramework\TestFrameworkAdapter;
use Infection\AbstractTestFramework\TestFrameworkAdapterFactory;
use Raneomik\InfectionTestFramework\Tester\Command\CommandLineBuilder;
use Raneomik\InfectionTestFramework\Tester\Command\CommandScriptBuilder;
use Raneomik\InfectionTestFramework\Tester\Command\InitialTestRunCommandBuilder;
use Raneomik\InfectionTestFramework\Tester\Config\MutationConfigBuilderFactory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final readonly class TesterAdapterFactory implements TestFrameworkAdapterFactory
{
    private const NAME = 'Tester';

    /**
     * @param array<string> $sourceDirectories
     */
    public static function create(
        string $testFrameworkExecutable,
        string $tmpDir,
        string $testFrameworkConfigPath,
        ?string $testFrameworkConfigDir,
        string $jUnitFilePath,
        string $projectDir,
        array $sourceDirectories,
        bool $skipCoverage,
    ): TestFrameworkAdapter {
        // Create the mutation config builder factory (dependency of TesterAdapter)
        $mutationConfigBuilderFactory = new MutationConfigBuilderFactory(
            $tmpDir,
            $projectDir,
        );

        // Create the initial test run command builder (dependency of TesterAdapter)
        $initialTestRunCommandBuilder = new InitialTestRunCommandBuilder(
            new CommandScriptBuilder(
                $sourceDirectories,
                $tmpDir,
                $projectDir,
                Path::makeRelative($jUnitFilePath, $tmpDir),
                $filesystem = new Filesystem(),
                new CommandLineBuilder(),
            )
        );

        return new TesterAdapter(
            self::NAME,
            $testFrameworkExecutable,
            $sourceDirectories,
            $filesystem,
            new VersionParser(self::NAME),
            new CommandLineBuilder(),
            $initialTestRunCommandBuilder,
            $mutationConfigBuilderFactory->create(),
        );
    }

    public static function getAdapterName(): string
    {
        return 'tester';
    }

    public static function getExecutableName(): string
    {
        return 'tester';
    }
}
