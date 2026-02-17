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

use function array_map;
use function chmod;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageScriptGenerator;
use Raneomik\InfectionTestFramework\Tester\Script\Template\InitialTestRunTemplate;
use Raneomik\InfectionTestFramework\Tester\Script\Template\SetupScriptTemplate;
use function rtrim;
use function sprintf;
use Symfony\Component\Filesystem\Filesystem;
use function trim;

/**
 * Builder for initial test run command.
 * Encapsulates all logic for preparing coverage collection and test execution.
 */
final readonly class CommandScriptBuilder
{
    /** @var string[] */
    private array $absoluteSrcDirs;
    private string $coverageFragmentDir;

    /**
     * @param string[] $srcDirs
     */
    public function __construct(
        array $srcDirs,
        private string $tmpDir,
        private string $projectDir,
        private string $jUnitFilePath,
        private Filesystem $filesystem,
        private CommandLineBuilder $commandLineBuilder,
    ) {
        $this->absoluteSrcDirs = $this->prepareSrcDirs($srcDirs);
        $this->coverageFragmentDir = sprintf('%s/coverage-fragments', $this->tmpDir);
    }

    /**
     * Generate prepend script for coverage collection
     *
     * @return array{script: string, autoload: ?string}
     */
    public function buildCoverageScript(): array
    {
        return CoverageScriptGenerator::generate(
            $this->projectDir,
            $this->tmpDir,
            $this->absoluteSrcDirs,
            $this->coverageFragmentDir,
        );
    }

    /**
     * @param array{autoload: ?string, script: ?string} $coverageScriptData
     */
    public function getCoverageAutoloadPath(array $coverageScriptData): string
    {
        return $coverageScriptData['autoload'] ?? $this->projectDir . '/vendor/autoload.php';
    }

    /**
     * Generate the Tester setup script.
     *
     * @param array{
     *      script: string,
     *      autoload: ?string
     *  } $coverageScriptData
     *
     * @return string Path to the generated setup script
     */
    public function buildSetupScript(array $coverageScriptData): string
    {
        $scriptContent = SetupScriptTemplate::build(
            $this->getCoverageAutoloadPath($coverageScriptData),
            $coverageScriptData['script'],
            $this->absoluteSrcDirs[0] ?? ''
        );
        $scriptPath = sprintf('%s/tester_setup.php', $this->tmpDir);

        $this->filesystem->dumpFile($scriptPath, $scriptContent);
        @chmod($scriptPath, 0755);

        return $scriptPath;
    }

    /**
     * Generate the wrapper script.
     *
     * @param string[] $frameworkArgs
     * @param string[] $phpExtraArgs
     * @param array{
     *     script: string,
     *     autoload: ?string
     * } $coverageScriptData
     */
    public function buildInitialTestWrapper(
        string $testFrameworkExecutable,
        array $frameworkArgs,
        array $phpExtraArgs,
        array $coverageScriptData,
    ): string {
        // Build command using CommandLineBuilder
        $commandParts = $this->commandLineBuilder->build(
            $testFrameworkExecutable,
            $phpExtraArgs,
            $frameworkArgs
        );

        $wrapper = InitialTestRunTemplate::generateWrapper(
            $commandParts,
            $this->getCoverageAutoloadPath($coverageScriptData),
            $this->coverageFragmentDir,
            $this->tmpDir,
            $this->getJUnitTmpPath(),
        );

        $wrapperPath = sprintf('%s/run-initial-tester.php', $this->tmpDir);
        $this->filesystem->dumpFile($wrapperPath, $wrapper);
        @chmod($wrapperPath, 0755);

        return $wrapperPath;
    }

    public function getJUnitTmpPath(): string
    {
        return $this->tmpDir . '/' . $this->jUnitFilePath;
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
        return array_map(
            fn (string $d): string => sprintf(
                '%s/%s',
                rtrim($this->projectDir, '/'),
                trim($d, '/')
            ),
            $srcDirs
        );
    }
}
