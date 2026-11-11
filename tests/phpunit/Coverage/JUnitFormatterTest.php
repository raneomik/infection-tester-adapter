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
use function file_get_contents;
use function file_put_contents;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Raneomik\InfectionTestFramework\Tester\Coverage\JUnitFormatter;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use function Raneomik\Tests\InfectionTestFramework\Tester\fixtureFilesPath;
use function str_replace;
use function substr_count;

#[Group('unit')]
final class JUnitFormatterTest extends FileSystemTestCase
{
    public function test_it_formats_nothing(): void
    {
        self::assertFalse(JUnitFormatter::format('nothing'));
        self::assertFalse(JUnitFormatter::format(fixtureFilesPath('/tester/src/.gitkeep')));
    }

    #[TestWith(['<testsuites><testcase classname="incomplete'], 'invalid xml')]
    #[TestWith(['<testsuites><testsuite><testcase name="testFoo" classname="SomeClass.SomeTest"/></testsuite></testsuites>'], 'not a Tester report')]
    #[TestWith(['<testsuites><testcase classname="/path/T.php method=test-foo"/></testsuites>'], 'invalid method')]
    #[TestWith(['<testsuites><testcase classname="/path/T.php method=foo bar"/></testsuites>'], 'trailing garbage after method')]
    #[TestWith(['<testsuites><testcase classname="/path/T.php garbage"/></testsuites>'], 'trailing garbage after file')]
    #[TestWith(['<testsuites><testcase classname="&#10;/path/T.php"/></testsuites>'], 'leading garbage')]
    public function test_it_returns_false_without_tester_testcases(string $junit): void
    {
        file_put_contents($path = $this->tmp . '/junit.xml', $junit);

        self::assertFalse(JUnitFormatter::format($path));
        self::assertStringEqualsFile($path, $junit, 'left untouched');
    }

    public function test_it_formats_to_phpunit_structure(): void
    {
        $testCase = fixtureFilesPath('/tester/tests/TestCaseTest.php');
        $plain = fixtureFilesPath('/tester/tests/PlainTest.php');

        file_put_contents($path = $this->tmp . '/junit.xml', <<<XML
            <?xml version="1.0"?>
            <testsuites>
              <testsuite>
                <testcase classname="{$testCase} method=testFail" name="{$testCase} method=testFail" time="0.25" line="12">
                  <failure message="f">failed &amp; <![CDATA[<kept>]]></failure>
                </testcase>
                <testcase classname="{$testCase} method=testTrueFalseSame" name="{$testCase} method=testTrueFalseSame" time="0.5"/>
                <testcase classname="{$testCase} method=testString" name="{$testCase} method=testString"><error message="e"/></testcase>
                <testcase classname="{$testCase} method=testException" name="{$testCase} method=testException"><skipped/></testcase>
                <testcase classname="" name="{$plain}" time="1.5"><failure message="f1"/><failure message="f2"/></testcase>
                <testcase classname="/missing/MissingTest.php method=testFoo" name="ignored"/>
                <testcase classname="not a Tester test" name="neither"/>
              </testsuite>
            </testsuites>
            XML);

        // no output path: formats in place
        self::assertTrue(JUnitFormatter::format($path));

        self::assertStringEqualsFile($path, <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <testsuites>
              <testsuite name="Nette Test Suite" tests="6" errors="1" failures="3" skipped="1" assertions="8" time="2.253000">
                <testsuite name="App\Tester\Tests\TestCaseTest" file="{$testCase}" assertions="7" tests="4" errors="1" failures="1" skipped="1" time="0.752000">
                  <testcase name="testFail" file="{$testCase}" class="App\Tester\Tests\TestCaseTest" classname="App.Tester.Tests.TestCaseTest" assertions="1" time="0.25" line="12">
                    <failure message="f">failed &amp; <![CDATA[<kept>]]></failure>
                  </testcase>
                  <testcase name="testTrueFalseSame" file="{$testCase}" class="App\Tester\Tests\TestCaseTest" classname="App.Tester.Tests.TestCaseTest" assertions="4" time="0.5"/>
                  <testcase name="testString" file="{$testCase}" class="App\Tester\Tests\TestCaseTest" classname="App.Tester.Tests.TestCaseTest" assertions="1" time="0.001">
                    <error message="e"/>
                  </testcase>
                  <testcase name="testException" file="{$testCase}" class="App\Tester\Tests\TestCaseTest" classname="App.Tester.Tests.TestCaseTest" assertions="1" time="0.001">
                    <skipped/>
                  </testcase>
                </testsuite>
                <testsuite name="PlainTest" file="{$plain}" assertions="1" tests="1" errors="0" failures="2" skipped="0" time="1.500000">
                  <testcase name="test" file="{$plain}" class="PlainTest" classname="PlainTest" assertions="0" time="1.5">
                    <failure message="f1"/>
                    <failure message="f2"/>
                  </testcase>
                </testsuite>
                <testsuite name="MissingTest" file="/missing/MissingTest.php" assertions="0" tests="1" errors="0" failures="0" skipped="0" time="0.001000">
                  <testcase name="testFoo" file="/missing/MissingTest.php" class="MissingTest" classname="MissingTest" assertions="0" time="0.001"/>
                </testsuite>
              </testsuite>
            </testsuites>

            XML);
    }

    public function test_it_formats_plain_junit(): void
    {
        [$junit, $output, $original] = $this->prepareFiles('/plain-junit.dist.xml');

        self::assertTrue(JUnitFormatter::format($junit, $output));
        self::assertStringEqualsFile($junit, $original, 'source left untouched');

        $content = (string) file_get_contents($output);

        self::assertSame(5, substr_count($content, '<testcase '));
        self::assertSame(5 + 1, substr_count($content, '<testsuite '), 'one suite per plain test file + the main one');
    }

    public function test_it_formats_testcase_junit(): void
    {
        [$junit, $output, $original] = $this->prepareFiles('/testcase-junit.dist.xml');

        self::assertTrue(JUnitFormatter::format($junit, $output));

        $content = (string) file_get_contents($output);

        self::assertSame(
            substr_count($original, '<testcase '),
            substr_count($content, '<testcase '),
        );
        self::assertSame(
            substr_count($original, ' method=') / 2,
            substr_count($content, '<testcase name="test'),
            'Tester has method name in "classname" & "name" -> Divided by 2',
        );
    }

    /**
     * @return array{string, string, string} junit path, output path, junit content
     */
    private function prepareFiles(string $distJunit): array
    {
        $content = str_replace('%root%', dirname(__DIR__, 3), (string) file_get_contents(fixtureFilesPath($distJunit)));

        file_put_contents($junit = $this->tmp . '/junit.xml', $content);

        return [$junit, $this->tmp . '/formatted.xml', $content];
    }
}
