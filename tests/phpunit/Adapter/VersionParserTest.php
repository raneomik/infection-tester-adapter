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

namespace Raneomik\Tests\InfectionTestFramework\Tester\Adapter;

use Infection\AbstractTestFramework\InvalidVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Raneomik\InfectionTestFramework\Tester\VersionParser;

final class VersionParserTest extends TestCase
{
    private VersionParser $versionParser;

    protected function setUp(): void
    {
        $this->versionParser = new VersionParser('TestTester');
    }

    #[DataProvider('versionProvider')]
    public function test_it_parses_version_from_string(string $content, string $expectedVersion): void
    {
        $result = $this->versionParser->parse($content);

        self::assertSame($expectedVersion, $result);
    }

    public function test_it_throws_exception_when_content_has_no_version_substring(): void
    {
        self::expectExceptionObject(
            new InvalidVersion(
                'Could not recognise the test framework version for TestTester for the value "abc".',
            ),
        );

        $this->versionParser->parse('abc');
    }

    /**
     * @return iterable<string, array<array-key, string>>
     */
    public static function versionProvider(): iterable
    {
        yield 'nominal stable' => ['2.6.0', '2.6.0'];

        yield 'nominal development' => ['2.6.0-dev', '2.6.0-dev'];

        yield 'stable variant' => ['v2.6.0', '2.6.0'];

        yield 'development variant' => ['v2.6.0-dev', '2.6.0-dev'];

        yield 'patch' => ['2.6.0-patch', '2.6.0-patch'];

        yield 'versioned patch' => ['2.6.0-patch.0', '2.6.0-patch.0'];

        yield 'RC' => ['2.6.0-rc', '2.6.0-rc'];

        yield 'with spaces' => [' 2.6.0 ', '2.6.0'];

        yield 'nonsense suffix 0' => ['2.6.0foo', '2.6.0'];

        yield 'nonsense suffix 1' => ['2.6.0-foo', '2.6.0-foo'];

        yield 'uppercase RC' => ['2.6.0-RC', '2.6.0-RC'];

        yield 'versioned RC' => ['2.6.0-rc.0', '2.6.0-rc.0'];
    }
}
