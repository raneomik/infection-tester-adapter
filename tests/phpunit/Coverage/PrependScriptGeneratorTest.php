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

namespace Raneomik\Tests\InfectionTestFramework\Tester\Coverage;

use function dirname;
use function mkdir;
use PHPUnit\Framework\Attributes\Group;
use Raneomik\InfectionTestFramework\Tester\Coverage\PrependScriptGenerator;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use RuntimeException;
use function touch;
use function var_export;

#[Group('unit')]
final class PrependScriptGeneratorTest extends FileSystemTestCase
{
    public function test_it_fails_without_autoload(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find Composer autoload.php');

        (new PrependScriptGenerator())->generate('/not/existing/project', $this->tmp, [], $this->tmp . '/fragments');
    }

    public function test_it_generates_script_listing_php_sources_once(): void
    {
        mkdir($this->tmp . '/src/Sub/Empty.php', recursive: true);
        touch($upper = $this->tmp . '/src/A.PHP');
        touch($this->tmp . '/src/readme.txt');
        touch($nested = $this->tmp . '/src/Sub/B.php');

        $parts = (new PrependScriptGenerator())->generate(
            // no vendor here: found in a parent directory
            dirname(__DIR__) . '/Fixtures/Files/tester',
            $this->tmp,
            // overlapping: Sub/B.php must be listed once
            [$this->tmp . '/src/Sub', $this->tmp . '/src'],
            $this->tmp . '/fragments',
        );

        self::assertSame([
            'script' => $script = $this->tmp . '/coverage_prepend.php',
            'autoload' => $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php',
        ], $parts);

        self::assertFileContains('require_once ' . var_export($autoload, true) . ';', $script);
        self::assertFileContains(var_export($this->tmp . '/fragments', true), $script);
        self::assertFileContains(var_export([$nested, $upper], true), $script);
    }
}
