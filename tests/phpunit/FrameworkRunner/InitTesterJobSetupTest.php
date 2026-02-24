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

namespace Raneomik\Tests\InfectionTestFramework\Tester\FrameworkRunner;

use function dirname;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Raneomik\InfectionTestFramework\Tester\FrameworkRunner\InitTesterJobSetup;
use function realpath;
use Tester\Runner\Runner;

#[Group('unit')]
final class InitTesterJobSetupTest extends TestCase
{
    public function test_it_configures_runner(): void
    {
        $fixtures = realpath(dirname(__DIR__)) . '/Fixtures/Files/tester/src';

        $runner = $this->createMock(Runner::class);

        $runner->expects(self::exactly(4))
            ->method('addPhpIniOption')
            ->willReturnCallback(static function (string $name, string $value) use ($fixtures): void {
                match ($name) {
                    'xdebug.mode' => self::assertSame('coverage', $value),
                    'xdebug.start_with_request' => self::assertSame('yes', $value),
                    'pcov.directory' => self::assertSame($fixtures, $value),
                    'pcov.enabled' => self::assertSame('1', $value),
                    'register_argc_argv' => self::assertSame('1', $value),
                    'auto_prepend_file' => self::assertSame('test', $value),
                    default => self::fail('Unexpected setup: ' . $name),
                };
            });

        InitTesterJobSetup::configure(
            $runner,
            'test',
            [$fixtures],
        );
    }
}
