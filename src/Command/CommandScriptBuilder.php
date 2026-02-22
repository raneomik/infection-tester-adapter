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

use Raneomik\InfectionTestFramework\Tester\Coverage\PrependScriptGenerator;
use Raneomik\InfectionTestFramework\Tester\Script\Template\InitialTestRunTemplate;
use Raneomik\InfectionTestFramework\Tester\Script\Template\SetupScriptTemplate;
use function realpath;
use function rtrim;
use function sprintf;
use Symfony\Component\Filesystem\Filesystem;
use function trim;

/**
 * Builder for initial test run command.
 * Encapsulates all logic for preparing coverage collection and test execution.
 */
final class CommandScriptBuilder
{
    /** @var string[] */
    private readonly array $absoluteSrcDirs;

    private readonly string $coverageFragmentDir;

    /**
     * @var array{
     *       script: string,
     *       autoload: ?string
     *   }|null
     */
    private ?array $coverageScriptParts = null;

    /**
     * @param string[] $srcDirs
     */
    public function __construct(
        array $srcDirs,
        private readonly string $tmpDir,
        private readonly string $projectDir,
        private readonly string $jUnitFilePath,
        private readonly Filesystem $filesystem,
        private readonly CommandLineBuilder $commandLineBuilder,
        private readonly PrependScriptGenerator $coverageScriptGenerator,
    ) {
        $this->absoluteSrcDirs = $this->prepareSrcDirs($srcDirs);
        $this->coverageFragmentDir = sprintf('%s/coverage-fragments', $this->tmpDir);

        $this->filesystem->mkdir($this->coverageFragmentDir);
    }

    /**
     * Generate the Tester setup script.
     *
     * @return string Path to the generated setup script
     */
    public function buildSetupScript(): string
    {
        $scriptContent = SetupScriptTemplate::build(
            $this->getCoverageAutoloadPath(),
            $this->getCoverageScript(),
            $this->absoluteSrcDirs[0] ?? ''
        );
        $scriptPath = sprintf('%s/tester-setup.php', $this->tmpDir);

        $this->filesystem->dumpFile($scriptPath, $scriptContent);
        $this->filesystem->chmod($scriptPath, 0755);

        return $scriptPath;
    }

    /**
     * Generate the wrapper script.
     *
     * @param string[] $frameworkArgs
     * @param string[] $phpExtraArgs
     */
    public function buildInitialTestWrapper(
        string $testFrameworkExecutable,
        array $phpExtraArgs = [],
        array $frameworkArgs = [],
    ): string {
        $commandParts = $this->commandLineBuilder->build(
            $testFrameworkExecutable,
            $phpExtraArgs,
            $frameworkArgs
        );

        $wrapper = InitialTestRunTemplate::generateWrapper(
            $commandParts,
            $this->getCoverageAutoloadPath(),
            $this->coverageFragmentDir,
            $this->tmpDir,
            $this->getJUnitTmpPath(),
        );

        $wrapperPath = sprintf('%s/run-initial-tester.php', $this->tmpDir);
        $this->filesystem->dumpFile($wrapperPath, $wrapper);
        $this->filesystem->chmod($wrapperPath, 0755);

        return $wrapperPath;
    }

    public function getJUnitTmpPath(): string
    {
        return $this->tmpDir . '/' . $this->jUnitFilePath;
    }

    /**
     * @return array{script: string, autoload: string|null}
     */
    private function generatedCoverageScriptParts(): array
    {
        return $this->coverageScriptParts ??= $this->coverageScriptGenerator->generate(
            $this->projectDir,
            $this->tmpDir,
            $this->absoluteSrcDirs,
            $this->coverageFragmentDir,
        );
    }

    private function getCoverageAutoloadPath(): string
    {
        return $this->generatedCoverageScriptParts()['autoload']
            ?? $this->projectDir . '/vendor/autoload.php'
        ;
    }

    private function getCoverageScript(): string
    {
        return $this->generatedCoverageScriptParts()['script'];
    }

    /**
     * Prepare absolute source directories.
     *
     * @param string[] $srcDirs
     *
     * @return string[]
     */
    private function prepareSrcDirs(array $srcDirs): array
    {
        $result = [];

        foreach ($srcDirs as $d) {
            $candidate = sprintf('%s/%s', rtrim($this->projectDir, '/'), trim($d, '/'));
            $resolved = realpath($candidate);

            if (false === $resolved) {
                continue;
            }

            $result[] = $resolved;
        }

        return $result;
    }
}
