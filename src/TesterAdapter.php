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

use function array_filter;
use function explode;
use Infection\AbstractTestFramework\TestFrameworkAdapter;
use Raneomik\InfectionTestFramework\Tester\Command\CommandLineBuilder;
use Raneomik\InfectionTestFramework\Tester\Command\InitialTestRunCommandBuilder;
use Raneomik\InfectionTestFramework\Tester\Config\MutationConfigBuilder;
use function sprintf;
use Symfony\Component\Process\Process;

final class TesterAdapter implements TestFrameworkAdapter
{
    public const NAME = 'tester';

    private ?string $cachedVersion = null;

    /**
     * @param string[] $srcDirs
     */
    public function __construct(
        private readonly string $name,
        private readonly string $testFrameworkExecutable,
        private readonly array $srcDirs,
        private readonly VersionParser $versionParser,
        private readonly CommandLineBuilder $commandLineBuilder,
        private readonly InitialTestRunCommandBuilder $initialTestRunCommandBuilder,
        private readonly MutationConfigBuilder $mutationConfigBuilder,
        private readonly TapTestChecker $tapTestChecker,
    ) {
    }

    public function hasJUnitReport(): bool
    {
        return true;
    }

    public function testsPass(string $output): bool
    {
        return $this->tapTestChecker->testsPass($output);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInitialTestRunCommandLine(string $extraOptions, array $phpExtraArgs, bool $skipCoverage): array
    {
        if ($skipCoverage) {
            return [];
        }

        $baseArguments = $this->prepareArgumentsAndOptions($extraOptions);

        // Delegate all initial test run logic to the builder
        return $this->initialTestRunCommandBuilder->build(
            $this->testFrameworkExecutable,
            $baseArguments,
            $phpExtraArgs,
        );
    }

    /**
     * @return array<int, string>
     */
    public function getMutantCommandLine(
        array $coverageTests,
        string $mutatedFilePath,
        string $mutationHash,
        string $mutationOriginalFilePath,
        string $extraOptions = '',
    ): array {
        $wrapperPath = $this->mutationConfigBuilder->prepareMutant(
            $this->testFrameworkExecutable,
            $this->srcDirs,
            $coverageTests,
            $mutatedFilePath,
            $mutationHash,
            $mutationOriginalFilePath,
        );

        return ['php', $wrapperPath];
    }

    public function getVersion(): string
    {
        if (null !== $this->cachedVersion) {
            return $this->cachedVersion;
        }

        $testFrameworkVersionExecutable = $this->commandLineBuilder->build(
            $this->testFrameworkExecutable,
            [],
            ['-i'],
        );

        $process = new Process($testFrameworkVersionExecutable);
        $process->mustRun();

        return $this->cachedVersion ??= $this->versionParser->parse($process->getOutput());
    }

    public function getInitialTestsFailRecommendations(string $commandLine): string
    {
        return sprintf('Check the executed command to identify the problem: %s', $commandLine);
    }

    /**
     * @return string[]
     */
    private function prepareArgumentsAndOptions(string $extraOptions): array
    {
        return array_filter(
            explode(' ', $extraOptions),
            static fn (string $value): bool => '' !== $value,
        );
    }
}
