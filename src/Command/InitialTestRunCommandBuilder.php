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

namespace Raneomik\InfectionTestFramework\Tester\Command;

use function array_merge;
use function sprintf;

/**
 * Builder for initial test run command.
 * Encapsulates all logic for preparing coverage collection and test execution.
 */
final readonly class InitialTestRunCommandBuilder
{
    public function __construct(
        private CommandScriptBuilder $commandScriptBuilder,
    ) {
    }

    /**
     * Build the initial test run command with coverage collection.
     *
     * @param string[] $baseArguments
     * @param string[] $phpExtraArgs
     *
     * @return string[] Command to execute
     */
    public function build(
        string $testFrameworkExecutable,
        array $baseArguments,
        array $phpExtraArgs,
    ): array {
        // Generate coverage script readable by Infection (uses CoverageScriptGenerator::generate)
        $coverageScript = $this->commandScriptBuilder->buildCoverageScript();

        // Generate setup script for Tester Runner (uses JobSetup::configure)
        $setupScript = $this->commandScriptBuilder->buildSetupScript($coverageScript);

        // Build Tester framework arguments with --setup
        $frameworkArgs = array_merge(
            $baseArguments,
            [
                '--setup', $setupScript,
                '-o', sprintf('junit:%s', $this->commandScriptBuilder->getJUnitTmpPath()),
            ]
        );

        // Generate PHP wrapper
        $wrapperPath = $this->commandScriptBuilder->buildInitialTestWrapper(
            $testFrameworkExecutable,
            $frameworkArgs,
            $phpExtraArgs,
            $coverageScript,
        );

        return ['php', $wrapperPath];
    }
}
