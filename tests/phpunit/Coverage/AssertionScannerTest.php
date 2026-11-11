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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Raneomik\InfectionTestFramework\Tester\Coverage\AssertionScanner;

#[Group('unit')]
final class AssertionScannerTest extends TestCase
{
    /**
     * @param array<string, int> $expected
     */
    #[TestWith(['Assert::true(1); Any\Assert::true(1); \Any\Assert::true(1);', ['total' => 3]], 'every name form')]
    #[TestWith(['Foo\Bar::baz(); \Foo\Asserts::true(1); MyAssert::true(1);', ['total' => 0]], 'other classes')]
    #[TestWith(['$a = Assert::class; $b = Assert::FOO; $c = Assert::$d; Assert->true(1); Assert', ['total' => 0]], 'not a static call')]
    #[TestWith(['Assert /* why */ :: /** doc */ true (1);', ['total' => 1]], 'comments & whitespaces')]
    #[TestWith(['$r->expectCode(200); $r->denyHeader("X");', ['total' => 0]], 'expectations without HttpAssert')]
    #[TestWith(['HttpAssert::fetch($u)->expectCode(200)->denyHeader("X")->expect()->expected()->other();', ['total' => 2]], 'HttpAssert expectations')]
    #[TestWith(['use function strlen; $f = function () { Assert::true(1); }; $g = fn () => Assert::true(1);', ['total' => 2]], 'no method')]
    #[TestWith(['function &ref() { Assert::true(1); } function none() {}', ['ref' => 1, 'none' => 0, 'total' => 1]], 'by reference & empty')]
    #[TestWith(['abstract class A { abstract function foo(); } if (1) { Assert::true(1); }', ['foo' => 0, 'total' => 1]], 'bodiless method')]
    #[TestWith(['function a() { $s = "{$x} ${y}"; Assert::true(1); } function b() { Assert::true(1); }', ['a' => 1, 'b' => 1, 'total' => 2]], 'interpolation braces')]
    #[TestWith(['function a() { if (1) { Assert::true(1); } $f = function () { Assert::true(1); }; Assert::true(1); }', ['a' => 3, 'total' => 3]], 'nested blocks')]
    #[TestWith(['class T { function a() { Assert::true(1); function b() { Assert::true(1); } } }', ['b' => 1, 'a' => 2, 'total' => 2]], 'nested functions')]
    public function test_it_scans(string $code, array $expected): void
    {
        self::assertSame($expected, AssertionScanner::scan('<?php ' . $code));
    }
}
