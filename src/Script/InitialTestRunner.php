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

namespace Raneomik\InfectionTestFramework\Tester\Script;

use function fwrite;
use const PHP_EOL;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageMerger;
use const STDERR;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Executes the initial test run and merges coverage.
 * This class is instantiated from the generated wrapper script.
 */
final readonly class InitialTestRunner
{
    /**
     * @param string[] $commandParts
     */
    public function __construct(
        private array $commandParts,
        private string $coverageFragmentDir,
        private string $tmpDir,
        private string $tmpJunitPath,
    ) {
    }

    /**
     * Execute the test command and merge coverage.
     *
     * @return int Exit code from test execution
     */
    public function run(): int
    {
        // Execute tests with coverage collection
        $exitCode = $this->executeTesterCommand();

        // Merge coverage fragments, normalize JUnit, and fix test identifiers
        // All the mapping is done here AFTER tests execution
        $this->mergeCoverageFragments();

        return $exitCode;
    }

    /**
     * Static entry point for the generated wrapper script.
     *
     * @param string[] $commandParts
     */
    public static function execute(
        array $commandParts,
        string $coverageFragmentDir,
        string $tmpDir,
        string $tmpJunitPath,
    ): int {
        $runner = new self($commandParts, $coverageFragmentDir, $tmpDir, $tmpJunitPath);

        return $runner->run();
    }

    /**
     * Execute the tester command and capture output.
     */
    private function executeTesterCommand(): int
    {
        $process = new Process($this->commandParts);
        $process->setTimeout(null);
        $process->run(static function (string $type, string $buffer): void {
            echo $buffer;
        });

        return $process->getExitCode() ?? 1;
    }

    /**
     * Merge coverage fragments and normalize JUnit XML.
     */
    private function mergeCoverageFragments(): void
    {
        try {
            CoverageMerger::merge(
                $this->coverageFragmentDir,
                $this->tmpDir,
                $this->tmpJunitPath
            );
        } catch (Throwable $e) {
            // Log error but don't fail (coverage mapping is non-critical)
            fwrite(STDERR, PHP_EOL . '⚠️  Coverage test failed: ' . $e->getMessage() . PHP_EOL);
        }
    }
}
