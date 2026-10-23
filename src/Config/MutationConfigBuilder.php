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

namespace Raneomik\InfectionTestFramework\Tester\Config;

use function array_map;
use function array_unique;
use function array_values;
use function dirname;
use function in_array;
use Infection\AbstractTestFramework\Coverage\TestLocation;
use function is_file;
use Raneomik\InfectionTestFramework\Tester\Script\Template\MutantSetupScriptTemplate;
use Raneomik\InfectionTestFramework\Tester\Script\Template\MutationBootstrapTemplate;
use function sprintf;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Builder for mutant test execution configuration.
 * Encapsulates all logic for creating mutation bootstrap and configuration.
 */
final readonly class MutationConfigBuilder
{
    public function __construct(
        private string $tmpDir,
        private string $projectDir,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * Create a bootstrap file for mutant execution.
     */
    public function createMutationBootstrap(
        string $mutationHash,
        string $originalFilePath,
        string $mutatedFilePath,
    ): string {
        $bootstrapPath = sprintf('%s/mutant/bootstrap-%s.php', $this->tmpDir, $mutationHash);

        if (is_file($bootstrapPath)) {
            return $bootstrapPath;
        }

        $bootstrapContent = MutationBootstrapTemplate::generate(
            $this->findAutoloadPath(),
            $originalFilePath,
            $mutatedFilePath,
        );

        $this->filesystem->dumpFile($bootstrapPath, $bootstrapContent);

        return $bootstrapPath;
    }

    /**
     * Create a handler with additional output so infection can identify killedBy tests.
     */
    public function createMutantSetupScript(string $mutationHash): string
    {
        $setupPath = sprintf('%s/mutant/setup-%s.php', $this->tmpDir, $mutationHash);

        if (is_file($setupPath)) {
            return $setupPath;
        }

        $this->filesystem->dumpFile(
            $setupPath,
            MutantSetupScriptTemplate::generate($this->findAutoloadPath()),
        );

        return $setupPath;
    }

    /**
     * Build Tester command-line arguments for mutant execution.
     *
     * @param TestLocation[] $coverageTests Tests that cover the mutated code
     * @param string[] $baseArguments User's Tester options (--test-framework-options)
     *
     * @return string[]
     */
    public function buildMutantArguments(
        array $coverageTests,
        string $bootstrapPath,
        string $setupPath,
        array $baseArguments = [],
    ): array {
        // Only the PHP options of $baseArguments: its directory paths (tests/) or discovery options force Tester
        // to scan all tests even when we pass specific files, causing 4× slowdown.
        // For mutants, we only want to run the exact tests that cover the mutated code.
        // "--stop-on-fail": one failure kills the mutant: Infection's PHPUnit adapter does the same with "stopOnFailure"
        return [
            ...self::phpOptions($baseArguments),
            '-d', sprintf('auto_prepend_file=%s', $bootstrapPath),
            '--setup', $setupPath,
            '-j', '1',
            '--stop-on-fail',
            ...array_unique(array_map(
                static fn (TestLocation $testLocation): string => $testLocation->getFilePath() ?? '',
                $coverageTests
            )),
        ];
    }

    /**
     * Tester runs PHP with -n (no php.ini) unless told otherwise: without these, mutant runs may miss extensions.
     *
     * @param string[] $arguments
     *
     * @return string[]
     */
    private static function phpOptions(array $arguments): array
    {
        $arguments = array_values($arguments);
        $options = [];

        foreach ($arguments as $i => $argument) {
            if ('-C' === $argument) {
                $options[] = $argument;
            } elseif (in_array($argument, ['-c', '-d', '-p'], true) && isset($arguments[$i + 1])) {
                $options[] = $argument;
                $options[] = $arguments[$i + 1];
            }
        }

        return $options;
    }

    private function findAutoloadPath(): string
    {
        $autoloadPath = $this->projectDir . '/vendor/autoload.php';

        if (!is_file($autoloadPath)) {
            return dirname(__DIR__, 2) . '/vendor/autoload.php';
        }

        return $autoloadPath;
    }
}
