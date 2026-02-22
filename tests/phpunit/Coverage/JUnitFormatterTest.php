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

use function count;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use function preg_match_all;
use Raneomik\InfectionTestFramework\Tester\Coverage\JUnitFormatter;
use stdClass;
use function substr_count;
use function unlink;

#[Group('unit')]
final class JUnitFormatterTest extends TestCase
{
    private string $originalFilepath;

    private string $originalContent;

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        unlink(dirname(__DIR__) . '/Fixtures/Files/tmp.xml');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        file_put_contents(
            $this->originalFilepath,
            $this->originalContent
        );
    }

    public function test_it_formats_plain_junit(): void
    {
        $data = $this->prepareFiles('plain-junit.xml');

        $isOk = JUnitFormatter::format($data->original, $data->output);
        $content = file_get_contents($data->output) ?: '';

        self::assertTrue($isOk);
        self::assertNotSame($data->originalContent, $content);

        self::assertSame(
            substr_count($content, '<testcase ') + 1,
            substr_count($content, '<testsuite '),
        );
        self::assertNotSame(
            substr_count($data->originalContent, '<testsuite '),
            substr_count($content, '<testsuite '),
        );
    }

    public function test_it_formats_testcase_junit(): void
    {
        $data = $this->prepareFiles('testcase-junit.xml');

        $isOk = JUnitFormatter::format($data->original, $data->output);
        $content = file_get_contents($data->output) ?: '';

        self::assertTrue($isOk);
        self::assertNotSame($data->originalContent, $content);

        preg_match_all('/\bmethod=()(.*?)\1/', $data->originalContent, $mMethods);
        $methods = $mMethods[2];

        preg_match_all('/<testcase\s+name=(")(.*?)\1/', $content, $mNames);
        $names = $mNames[2];

        self::assertSame(
            count($names),
            count($methods) / 2,
            'Tester has method name in "classname" & "name" -> Divided by 2'
        );
    }

    /**
     * @return stdClass&object{
     *     original: string,
     *     output: string,
     *     originalContent: string,
     * }
     */
    private function prepareFiles(string $testJunitPath): stdClass
    {
        $fixtures = dirname(__DIR__) . '/Fixtures/Files';

        return (object) [
            'original' => $this->originalFilepath = $fixtures . '/' . $testJunitPath,
            'output' => $fixtures . '/tmp.xml',
            'originalContent' => $this->originalContent = file_get_contents($this->originalFilepath) ?: '',
        ];
    }
}
