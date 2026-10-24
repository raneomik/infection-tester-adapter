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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Raneomik\InfectionTestFramework\Tester\Coverage\AssertionsCounter;
use function Raneomik\Tests\InfectionTestFramework\Tester\fixtureFilesPath;

#[Group('unit')]
final class AssertionCounterTest extends TestCase
{
    public function test_it_counts_asserts(): void
    {
        $testPath = fixtureFilesPath('/tester/tests/PlainTest.php');

        self::assertSame(['total' => 1], AssertionsCounter::countAssertions($testPath));
        self::assertSame(['total' => 1], AssertionsCounter::countAssertions($testPath, ''), 'from cache');

        $testPath = fixtureFilesPath('/tester/tests/TestCaseTest.php');
        self::assertSame([
            'testFail' => 1,
            'testTrueFalseSame' => 4,
            'testString' => 1,
            'testException' => 1,
            'total' => 7,
        ], AssertionsCounter::countAssertions($testPath));
        self::assertSame(4, AssertionsCounter::countInMethod($testPath, 'testTrueFalseSame'));
        self::assertSame(1, AssertionsCounter::countInMethod($testPath, 'testFail'));

        $testPath = fixtureFilesPath('/tester/tests/MiscLargeTest.php');
        self::assertSame([
            'testSomethingLarge' => 11,
            'total' => 11,
        ], AssertionsCounter::countAssertions($testPath));
        self::assertSame(11, AssertionsCounter::countInMethod($testPath, 'testSomethingLarge'));
        self::assertSame(0, AssertionsCounter::countInMethod($testPath, 'testFail'));
    }

    public function test_it_counts_asserts_in_notfound_test(): void
    {
        $testPath = fixtureFilesPath('/tester/tests/NotFoundTest.php');
        self::assertSame(['total' => 0], AssertionsCounter::countAssertions($testPath));
        self::assertSame(['total' => 0], AssertionsCounter::countAssertions($testPath, ''), 'from cache');

        $testPath = fixtureFilesPath('/tester/tests/PlainTest.php');
        self::assertSame(0, AssertionsCounter::countInMethod($testPath, 'testNotHere'));
    }

    public function test_it_resets_cache(): void
    {
        $testPath = fixtureFilesPath('/tester/tests/PlainTest.php');
        AssertionsCounter::countAssertions($testPath);

        AssertionsCounter::reset();

        self::assertSame(['total' => 0], AssertionsCounter::countAssertions($testPath, ''), 'given content, not cache');

        // the cache is static: don't leave the empty entry to other tests
        AssertionsCounter::reset();
    }
}
