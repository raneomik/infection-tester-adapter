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

namespace Raneomik\InfectionTestFramework\Tester\Coverage;

use function extension_loaded;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\PcovDriver;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Filter;
use Tester\Helpers;

/**
 * Detects which PHP coverage driver is available.
 * Priority: PCOV > Xdebug
 *
 * @see \Raneomik\Tests\InfectionTestFramework\Tester\Coverage\CoverageDriverProviderTest
 */
final readonly class CoverageDriverProvider
{
    private ?string $driver;

    public function __construct()
    {
        $this->driver = $this->detect();
    }

    /**
     * Create a coverage driver with pcov > xdebug priority.
     */
    public function coverageDriver(Filter $filter): ?Driver
    {
        if ('pcov' === $this->driver) {
            return new PcovDriver($filter);
        }

        if ('xdebug' === $this->driver) {
            return new XdebugDriver($filter);
        }

        return null;
    }

    /**
     * @param string[] $srcDirs
     *
     * @return string[]
     */
    public function runnerOptions(array $srcDirs): array
    {
        return match ($this->driver) {
            'pcov' => $this->pcovRunnerOptions($srcDirs),
            'xdebug' => $this->xdebugRunnerOptions(),
            default => [],
        };
    }

    /**
     * Detect the available coverage driver.
     * Returns null if no driver is available.
     *
     * @return 'pcov'|'xdebug'|null
     */
    private function detect(): ?string
    {
        if (extension_loaded('pcov')) {
            return 'pcov';
        }

        if (extension_loaded('xdebug')) {
            return 'xdebug';
        }

        return null;
    }

    /**
     * @param string[] $srcDirs
     *
     * @return array<string, string>
     */
    private function pcovRunnerOptions(array $srcDirs): array
    {
        $options = ['pcov.enabled' => '1'];

        $options['pcov.directory'] = Helpers::findCommonDirectory($srcDirs);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function xdebugRunnerOptions(): array
    {
        return [
            'xdebug.mode' => 'coverage',
            'xdebug.start_with_request' => 'yes',
        ];
    }
}
