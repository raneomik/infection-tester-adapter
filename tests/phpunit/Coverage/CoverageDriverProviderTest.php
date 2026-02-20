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

/*
 * This file is part of the box project.
 *
 * (c) Kevin Herrera <kevin@herrera.io>
 *     Théo Fidry <theo.fidry@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Raneomik\Tests\InfectionTestFramework\Tester\Coverage;

use function getenv;
use function ini_get;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoverageDriverProvider;
use ReflectionProperty;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\PcovDriver;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Filter;

#[Group('unit')]
final class CoverageDriverProviderTest extends TestCase
{
    #[RequiresPhpExtension('pcov')]
    public function test_it_loads_pcov_driver(): void
    {
        $provider = $this->createProvider('pcov');
        self::assertInstanceOf(PcovDriver::class, $provider->coverageDriver(new Filter()));
    }

    #[RequiresPhpExtension('xdebug')]
    public function test_it_loads_xdebug_driver(): void
    {
        if (
            'coverage' !== getenv('XDEBUG_MODE')
            || 'coverage' !== ini_get('xdebug.mode')
        ) {
            self::markTestSkipped('Xdebug is not in coverage mode.');
        }

        $provider = $this->createProvider('xdebug');
        self::assertInstanceOf(XdebugDriver::class, $provider->coverageDriver(new Filter()));
    }

    /**
     * @return iterable<array{
     *     driver: string,
     *     iniOptions: string[],
     *     runnerOptions: array<string, string>
     * }>
     */
    public static function driverOptionCases(): iterable
    {
        yield [
            'driver' => 'pcov',
            'iniOptions' => [
                '-d', 'pcov.enabled=1',
                '-d', 'pcov.directory=test',
            ],
            'runnerOptions' => [
                'pcov.enabled' => '1',
                'pcov.directory' => 'test',
            ],
        ];

        yield [
            'driver' => 'xdebug',
            'iniOptions' => [
                '-d', 'xdebug.mode=coverage',
                '-d', 'xdebug.start_with_request=yes',
            ],
            'runnerOptions' => [
                'xdebug.mode' => 'coverage',
                'xdebug.start_with_request' => 'yes',
            ],
        ];
    }

    /**
     * @param string[] $iniOptions
     * @param array<string, string> $runnerOptions
     */
    #[DataProvider('driverOptionCases')]
    public function test_it_provides_driver_options(
        string $driver,
        array $iniOptions,
        array $runnerOptions,
    ): void {
        $coverageDriverProvider = $this->createProvider($driver);
        self::assertSame($iniOptions, $coverageDriverProvider->phpIniOptions('test'));
        self::assertSame($runnerOptions, $coverageDriverProvider->runnerOptions('test'));
    }

    private function createProvider(?string $driver = null): CoverageDriverProvider
    {
        $coverageDriverProvider = new CoverageDriverProvider();

        if (null !== $driver) {
            $reflectionProperty = new ReflectionProperty($coverageDriverProvider, 'driver');
            $reflectionProperty->setValue($coverageDriverProvider, $driver);
        }

        return $coverageDriverProvider;
    }
}
