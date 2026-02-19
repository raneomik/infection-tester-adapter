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

use function array_filter;
use function array_merge;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Builds command line for Tester test framework.
 *
 * Uses Tester's native options:
 * - `-p <path>` to specify PHP interpreter
 * - `-d <key=value>` to define INI entries
 */
final class CommandLineBuilder
{
    /**
     * Build command line for Tester.
     *
     * @param string[] $phpExtraArgs PHP INI options (will be converted to Tester -d options)
     * @param string[] $frameworkArgs Tester framework arguments
     *
     * @return string[]
     */
    public function build(string $testFrameworkExecutable, array $phpExtraArgs, array $frameworkArgs): array
    {
        $phpExtraArgs = $this->cleanup($phpExtraArgs);
        $frameworkArgs = $this->cleanup($frameworkArgs);

        $command = [$testFrameworkExecutable];

        if ([] !== $phpExtraArgs) {
            $command[] = '-p';
            $command[] = $this->findPhp();
        }

        // Merge all arguments
        return array_merge($command, $phpExtraArgs, $frameworkArgs);
    }

    /**
     * Find PHP executable.
     */
    private function findPhp(): string
    {
        $phpExec = (new PhpExecutableFinder())->find(false);

        if (false === $phpExec) {
            throw new RuntimeException('PHP executable not found');
        }

        return $phpExec;
    }

    /**
     * @param string[] $input
     *
     * @return string[]
     */
    private function cleanup(array $input): array
    {
        return array_filter($input, static fn (string $value): bool => '' !== $value);
    }
}
