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

use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Raneomik\InfectionTestFramework\Tester\Compatibility\TestSizeResolver;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoveringTestIdentifier;
use function Raneomik\Tests\InfectionTestFramework\Tester\fixtureFilesPath;

#[Group('unit')]
final class CoveringTestIdentifierTest extends TestCase
{
    /**
     * @return iterable<array{
     *     arg: string,
     *     file: string,
     *     expectedId: string
     * }>
     */
    public static function fileToIdentifyCases(): iterable
    {
        yield 'non test file' => [
            'arg' => '',
            'file' => '/tester/tests/bootstrap.php',
            'expectedId' => 'not-to-cover',
            'expectedSize' => TestSizeResolver::unknown(),
        ];

        yield 'test with no test but Test.php suffix' => [
            'arg' => '',
            'file' => '/tester/tests/NamespacedPlainTest.php',
            'expectedId' => 'App\Tester\Tests\NamespacedPlainTest::test',
            'expectedSize' => TestSizeResolver::unknown(),
        ];

        yield 'plain test without namespace' => [
            'arg' => '',
            'file' => '/tester/tests/PlainTest.php',
            'expectedId' => 'PlainTest::test',
            'expectedSize' => TestSizeResolver::unknown(),
        ];

        yield 'plain test with virtual method' => [
            'arg' => '--method=test',
            'file' => '/tester/tests/PlainTest.php',
            'expectedId' => 'PlainTest::test',
            'expectedSize' => TestSizeResolver::small(),
        ];

        yield 'testCase with no method' => [
            'arg' => '',
            'file' => '/tester/tests/TestCaseTest.php',
            'expectedId' => \App\Tester\Tests\TestCaseTest::class . '::test',
            'expectedSize' => TestSizeResolver::unknown(),
        ];

        yield 'testCase with existing method' => [
            'arg' => '--method=testString',
            'file' => '/tester/tests/TestCaseTest.php',
            'expectedId' => \App\Tester\Tests\TestCaseTest::class . '::testString',
            'expectedSize' => TestSizeResolver::small(),
        ];

        yield 'testCase with largest assert count' => [
            'arg' => '--method=testTrueFalseSame',
            'file' => '/tester/tests/TestCaseTest.php',
            'expectedId' => \App\Tester\Tests\TestCaseTest::class . '::testTrueFalseSame',
            'expectedSize' => TestSizeResolver::medium(),
        ];

        yield 'large testCase' => [
            'arg' => '--method=testSomethingLarge',
            'file' => '/tester/tests/MiscLargeTest.php',
            'expectedId' => \App\Tester\Tests\MiscLargeTest::class . '::testSomethingLarge',
            'expectedSize' => TestSizeResolver::large(),
        ];
    }

    #[DataProvider('fileToIdentifyCases')]
    public function test_it_identifies_test(
        string $arg,
        string $file,
        string $expectedId,
        mixed $expectedSize,
    ): void {
        $_SERVER['argv'] = ['php', $arg];

        $filepath = fixtureFilesPath($file);
        $identifier = new CoveringTestIdentifier([$filepath]);

        self::assertSame($expectedId, $identifier->identifyTest());
        self::assertSame($expectedSize, $identifier->identifySize());
    }

    /**
     * @return Iterator<array<string, string>>
     */
    public static function idCases(): iterable
    {
        yield 'with method' => [
            'method' => 'testSomething',
            'class' => 'class',
            'namespace' => 'namespace',
            'filename' => 'file.php',
            'expectedId' => 'namespace\class::testSomething',
        ];

        yield 'without method' => [
            'method' => '',
            'class' => 'class',
            'namespace' => 'namespace',
            'filename' => 'file.php',
            'expectedId' => 'namespace\class::test',
        ];

        yield 'without class' => [
            'method' => '',
            'class' => '',
            'namespace' => 'namespace',
            'filename' => 'file.php',
            'expectedId' => 'namespace\file::test',
        ];

        yield 'namespaced phpt file' => [
            'method' => '',
            'class' => '',
            'namespace' => 'namespace',
            'filename' => 'file.phpt',
            'expectedId' => 'namespace\file::test',
        ];

        yield 'without namespace phpt file' => [
            'method' => '',
            'class' => '',
            'namespace' => '',
            'filename' => 'file.phpt',
            'expectedId' => 'file::test',
        ];
    }

    #[DataProvider('idCases')]
    public function test_it_gets_id(
        string $method,
        string $class,
        string $namespace,
        string $filename,
        string $expectedId,
    ): void {
        $identifier = new CoveringTestIdentifier();

        $testId = $identifier->getTestId(
            $method,
            $class,
            $namespace,
            $filename,
        );

        self::assertSame($expectedId, $testId);
    }
}
