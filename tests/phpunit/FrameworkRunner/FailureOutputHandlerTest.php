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

use Iterator;
use function ob_get_clean;
use function ob_start;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Raneomik\InfectionTestFramework\Tester\FrameworkRunner\FailureOutputHandler;
use function Raneomik\Tests\InfectionTestFramework\Tester\fixtureFilesPath;
use Tester\Runner\Test;

#[Group('unit')]
final class FailureOutputHandlerTest extends TestCase
{
    /**
     * @return Iterator<array<string, (string | Test)>>
     */
    public static function useCases(): iterable
    {
        yield [
            'test' => (new Test(fixtureFilesPath('/tester/tests/TestCaseTest.php')))
                ->withResult(Test::Passed, null),
            'expected' => '',
        ];

        yield [
            'test' => (new Test(fixtureFilesPath('/tester/tests/PlainTest.php')))
                ->withResult(Test::Failed, null),
            'expected' => <<<XX
            
            Tests: 1, Assertions: 1
            PlainTest::test
            
            
            XX,
        ];

        yield [
            'test' => (new Test(fixtureFilesPath('/tester/tests/TestCaseTest.php')))
                ->withResult(Test::Failed, 'it failed'),
            'expected' => <<<XX
            
            Tests: 1, Assertions: 7
            App\Tester\Tests\TestCaseTest::test
            
            
            XX,
        ];

        yield [
            'test' => (new Test(fixtureFilesPath('/tester/tests/TestCaseTest.php'), 'testMethod'))
                ->withResult(Test::Failed, 'it failed'),
            'expected' => <<<XX
            
            Tests: 1, Assertions: 7
            App\Tester\Tests\TestCaseTest::testMethod
            
            
            XX,
        ];

        yield [
            'test' => (new Test(fixtureFilesPath('/tester/tests/MiscLargeTest.php'), 'testLargeMethod'))
                ->withResult(Test::Failed, 'it failed'),
            'expected' => <<<XX
            
            Tests: 1, Assertions: 11
            App\Tester\Tests\MiscLargeTest::testLargeMethod
            
            
            XX,
        ];
    }

    #[DataProvider('useCases')]
    public function test_it_outputs(Test $test, string $expected): void
    {
        ob_start();
        (new FailureOutputHandler())->finish($test);
        $output = ob_get_clean();

        self::assertSame($expected, $output);
    }
}
