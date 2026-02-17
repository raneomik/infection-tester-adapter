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

use function array_merge;
use function dirname;
use function is_file;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageDriverDetector;
use Raneomik\InfectionTestFramework\Tester\Script\Template\MutationBootstrapTemplate;
use function sprintf;
use function trim;

/**
 * Builder for mutant test execution configuration.
 * Encapsulates all logic for creating mutation bootstrap and configuration.
 */
final readonly class MutationConfigBuilder
{
    public function __construct(
        private string $tmpDir,
        private string $projectDir,
        private ?string $originalBootstrap,
    ) {
    }

    /**
     * Create a bootstrap file for mutant execution.
     *
     * @return array{path: string, content: string}
     */
    public function createMutationBootstrap(
        string $mutationHash,
        string $originalFilePath,
        string $mutatedFilePath,
    ): array {
        $bootstrapContent = MutationBootstrapTemplate::generate(
            $this->originalBootstrap,
            $this->findAutoloadPath(),
            $originalFilePath,
            $mutatedFilePath,
        );

        $bootstrapPath = sprintf('%s/bootstrap-mutant-%s.php', $this->tmpDir, $mutationHash);

        return [
            'path' => $bootstrapPath,
            'content' => $bootstrapContent,
        ];
    }

    /**
     * Create output directory for mutant test results.
     */
    public function createOutputDirectory(string $mutationHash): string
    {
        return sprintf('%s/%s', $this->tmpDir, $mutationHash);
    }

    /**
     * Build Tester command-line arguments for mutant execution.
     *
     * @param string[] $baseArguments Base arguments from test configuration
     *
     * @return string[]
     */
    public function buildMutantArguments(
        array $baseArguments,
        string $bootstrapPath,
        string $outputDir,
    ): array {
        return array_merge(
            $baseArguments,
            [
                '-j', '1',
                $bootstrapPath,
                '-o', sprintf('junit:%s/junit.xml', $outputDir),
            ]
        );
    }

    /**
     * Build PHP extra arguments for PCOV coverage.
     *
     * @param string[] $srcDirs
     *
     * @return string[]
     */
    public function buildPhpExtraArgs(array $srcDirs): array
    {
        if ([] === $srcDirs) {
            return [];
        }

        $firstSrc = $srcDirs[0];
        $pcovDir = sprintf('%s/%s', $this->projectDir, trim($firstSrc, '/'));

        $detector = new CoverageDriverDetector();

        return $detector->buildPhpIniOptions($pcovDir);
    }

    /**
     * Find path to vendor/autoload.php
     */
    private function findAutoloadPath(): string
    {
        $autoloadPath = $this->projectDir . '/vendor/autoload.php';

        if (!is_file($autoloadPath)) {
            $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
        }

        return $autoloadPath;
    }
}
