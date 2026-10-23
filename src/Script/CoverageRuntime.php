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

use function bin2hex;
use function file_put_contents;
use function get_included_files;
use function getmypid;
use function is_dir;
use function mkdir;
use function random_bytes;
use function random_int;
use Raneomik\InfectionTestFramework\Tester\Compatibility\TestStatus;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageDriverProvider;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoveringTestIdentifier;
use function register_shutdown_function;
use function rtrim;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;
use function serialize;
use function sprintf;
use Throwable;

/**
 * Runtime coverage collector for Nette Tester jobs.
 * Starts coverage at script start and dumps on shutdown.
 */
final readonly class CoverageRuntime
{
    private CoveringTestIdentifier $coveringTestIdentifier;

    private CoverageDriverProvider $coverageDriverProvider;

    /**
     * @param list<string> $srcFiles
     */
    private function __construct(
        private string $fragmentDir,
        private array $srcFiles,
    ) {
        $this->coveringTestIdentifier = new CoveringTestIdentifier(get_included_files());
        $this->coverageDriverProvider = new CoverageDriverProvider();
    }

    /**
     * @param string $fragmentDir directory to write coverage fragments
     * @param list<string> $srcFiles absolute paths of the source files to cover, listed once by PrependScriptGenerator
     */
    public static function start(string $fragmentDir, array $srcFiles): void
    {
        if ('' === $fragmentDir || [] === $srcFiles) {
            return;
        }

        if (!is_dir($fragmentDir)) {
            @mkdir($fragmentDir, 0777, true);
        }

        (new self($fragmentDir, $srcFiles))->cover();
    }

    private function cover(): void
    {
        $testId = $this->coveringTestIdentifier->identifyTest();

        // an id missing from the JUnit report makes Infection abort ("test not found"): leave such tests uncovered
        if (CoveringTestIdentifier::NOT_TO_COVER === $testId) {
            return;
        }

        $filter = new Filter();
        $filter->includeFiles($this->srcFiles);

        $driver = $this->coverageDriverProvider->coverageDriver($filter);

        if (null === $driver) {
            return;
        }

        $codeCoverage = new CodeCoverage($driver, $filter);

        $testSize = $this->coveringTestIdentifier->identifySize();
        // @phpstan-ignore-next-line
        $codeCoverage->start($testId, $testSize);

        register_shutdown_function(function () use ($codeCoverage): void {
            $this->dumpCoverage($codeCoverage);
        });
    }

    /**
     * Dump coverage data to a fragment file.
     */
    private function dumpCoverage(CodeCoverage $codeCoverage): void
    {
        try {
            // @phpstan-ignore-next-line
            $codeCoverage->stop(true, TestStatus::success());
        } catch (Throwable) {
            // Ignore errors during stop
        }

        $filename = $this->generateFragmentFilename();
        $path = rtrim($this->fragmentDir, '/') . '/' . $filename;

        @file_put_contents($path, serialize($codeCoverage));
    }

    /**
     * Generate a unique filename for a coverage fragment.
     */
    private function generateFragmentFilename(): string
    {
        $pid = getmypid() ?: random_int(1, 999999);
        $random = bin2hex(random_bytes(4));

        return sprintf('cc-%s-%s.phpser', $pid, $random);
    }
}
