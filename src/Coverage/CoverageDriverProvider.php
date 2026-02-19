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

use function class_exists;
use function extension_loaded;
use function is_string;
use const PHP_SAPI;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\PcovDriver;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Filter;

/**
 * Detects which PHP coverage driver is available.
 * Priority: PCOV > PHPDBG > Xdebug (following Tester philosophy)
 */
final readonly class CoverageDriverProvider
{
    private ?string $driver;

    public function __construct()
    {
        $this->driver = $this->detect();
    }

    /**
     * Create a coverage driver with pcov > phpdbg > xdebug priority.
     */
    public function coverageDriver(Filter $filter): ?Driver
    {
        if ('pcov' === $this->driver) {
            return new PcovDriver($filter);
        }

        if ('phpdbg' === $this->driver) {
            $phpdbgDriverClass = 'SebastianBergmann\\CodeCoverage\\Driver\\PhpdbgDriver';

            if (class_exists($phpdbgDriverClass)) {
                /* @phpstan-ignore-next-line */
                return new $phpdbgDriverClass($filter);
            }
        }

        if ('xdebug' === $this->driver) {
            return new XdebugDriver($filter);
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function phpIniOptions(?string $pcovDir = null): array
    {
        return match ($this->driver) {
            'pcov' => $this->pcovIniOptions($pcovDir),
            'phpdbg' => [], // PHPDBG doesn't need INI options
            'xdebug' => $this->xdebugIniOptions(),
            default => [],
        };
    }

    /**
     * @return string[]
     */
    public function phpIniRunnerOptions(?string $pcovDir = null): array
    {
        return match ($this->driver) {
            'pcov' => $this->pcovRunnerOptions($pcovDir),
            'phpdbg' => [], // PHPDBG doesn't need INI options
            'xdebug' => $this->xdebugRunnerOptions(),
            default => [],
        };
    }

    /**
     * Detect the available coverage driver.
     * Returns null if no driver is available.
     *
     * @return 'pcov'|'phpdbg'|'xdebug'|null
     */
    private function detect(): ?string
    {
        if (extension_loaded('pcov')) {
            return 'pcov';
        }

        if (PHP_SAPI === 'phpdbg') {
            return 'phpdbg';
        }

        if (extension_loaded('xdebug')) {
            return 'xdebug';
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function pcovIniOptions(?string $pcovDir): array
    {
        $options = ['-d', 'pcov.enabled=1'];

        if (is_string($pcovDir) && '' !== $pcovDir) {
            $options[] = '-d';
            $options[] = 'pcov.directory=' . $pcovDir;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function pcovRunnerOptions(?string $pcovDir): array
    {
        $options = ['pcov.enabled' => '1'];

        if (is_string($pcovDir) && '' !== $pcovDir) {
            $options['pcov.directory'] = $pcovDir;
        }

        return $options;
    }

    /**
     * @return string[]
     */
    private function xdebugIniOptions(): array
    {
        return [
            '-d', 'xdebug.mode=coverage',
            '-d', 'xdebug.start_with_request=yes',
        ];
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
