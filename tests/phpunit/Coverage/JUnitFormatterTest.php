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

use function count;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use function preg_match_all;
use Raneomik\InfectionTestFramework\Tester\Coverage\JUnitFormatter;
use stdClass;
use function str_replace;
use function substr_count;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[Group('unit')]
final class JUnitFormatterTest extends TestCase
{
    private static string $fixtureDir;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$fixtureDir ??= dirname(__DIR__) . '/Fixtures/Files';
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        unlink(dirname(__DIR__) . '/Fixtures/Files/tmp.xml');
    }

    public function test_it_formats_nothing(): void
    {
        $isOk = JUnitFormatter::format('nothing');
        self::assertFalse($isOk);

        $isOk = JUnitFormatter::format(self::$fixtureDir . '/src/.gitkeep');
        self::assertFalse($isOk);
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

    public function test_it_returns_false_on_invalid_xml(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'junit_test_');
        file_put_contents($tmp, '<testsuites><testcase classname="incomplete');

        try {
            self::assertFalse(JUnitFormatter::format($tmp));
        } finally {
            unlink($tmp);
        }
    }

    public function test_it_returns_false_when_no_tester_format_testcases(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'junit_test_');
        file_put_contents($tmp, '<?xml version="1.0"?><testsuites><testsuite><testcase name="testFoo" classname="SomeClass.SomeTest"/></testsuite></testsuites>');

        try {
            self::assertFalse(JUnitFormatter::format($tmp));
        } finally {
            unlink($tmp);
        }
    }

    public function test_it_uses_name_attribute_when_classname_is_empty(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'junit_test_');
        $out = (string) tempnam(sys_get_temp_dir(), 'junit_out_');
        file_put_contents($tmp, '<?xml version="1.0"?><testsuites><testsuite><testcase classname="" name="/path/to/SomeTest.php method=testFoo"/></testsuite></testsuites>');

        try {
            self::assertTrue(JUnitFormatter::format($tmp, $out));
            self::assertStringContainsString('name="testFoo"', file_get_contents($out) ?: '');
        } finally {
            unlink($tmp);
            unlink($out);
        }
    }

    public function test_it_preserves_time_and_line_attributes(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'junit_test_');
        $out = (string) tempnam(sys_get_temp_dir(), 'junit_out_');
        file_put_contents($tmp, '<?xml version="1.0"?><testsuites><testsuite><testcase classname="/path/SomeTest.php method=testFoo" name="/path/SomeTest.php method=testFoo" time="0.123" line="42"/></testsuite></testsuites>');

        try {
            self::assertTrue(JUnitFormatter::format($tmp, $out));
            $content = file_get_contents($out) ?: '';
            self::assertStringContainsString('time="0.123"', $content);
            self::assertStringContainsString('line="42"', $content);
        } finally {
            unlink($tmp);
            unlink($out);
        }
    }

    public function test_it_counts_and_propagates_failures_errors_and_skipped(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'junit_test_');
        $out = (string) tempnam(sys_get_temp_dir(), 'junit_out_');
        file_put_contents($tmp,
            '<?xml version="1.0"?><testsuites><testsuite>'
            . '<testcase classname="/path/T.php method=testA" name="/path/T.php method=testA"><failure message="f">msg</failure></testcase>'
            . '<testcase classname="/path/T.php method=testB" name="/path/T.php method=testB"><error message="e">msg</error></testcase>'
            . '<testcase classname="/path/T.php method=testC" name="/path/T.php method=testC"><skipped/></testcase>'
            . '</testsuite></testsuites>'
        );

        try {
            self::assertTrue(JUnitFormatter::format($tmp, $out));
            $content = file_get_contents($out) ?: '';
            self::assertStringContainsString('<failure', $content);
            self::assertStringContainsString('<error', $content);
            self::assertStringContainsString('<skipped', $content);
            self::assertMatchesRegularExpression('/failures="1"/', $content);
            self::assertMatchesRegularExpression('/errors="1"/', $content);
            self::assertMatchesRegularExpression('/skipped="1"/', $content);
        } finally {
            unlink($tmp);
            unlink($out);
        }
    }

    public function test_it_extracts_class_and_namespace_from_readable_file(): void
    {
        $phpFile = sys_get_temp_dir() . '/JUnitFormatterTestClass.php';
        file_put_contents($phpFile, "<?php\nnamespace My\\Custom\\NS;\nfinal class MyTestClass {}\n");
        $tmp = (string) tempnam(sys_get_temp_dir(), 'junit_test_');
        $out = (string) tempnam(sys_get_temp_dir(), 'junit_out_');
        file_put_contents($tmp,
            '<?xml version="1.0"?><testsuites><testsuite>'
            . '<testcase classname="' . $phpFile . ' method=testFoo" name="' . $phpFile . ' method=testFoo"/>'
            . '</testsuite></testsuites>'
        );

        try {
            self::assertTrue(JUnitFormatter::format($tmp, $out));
            $content = file_get_contents($out) ?: '';
            self::assertStringContainsString('My\Custom\NS', $content);
            self::assertStringContainsString('MyTestClass', $content);
        } finally {
            unlink($phpFile);
            unlink($tmp);
            unlink($out);
        }
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
        $original = $fixtures . '/' . $testJunitPath;

        $originalDist = str_replace('.xml', '.dist.xml', $original);
        $originalContent = file_get_contents($originalDist) ?: '';

        file_put_contents($original, $originalContent);

        return (object) [
            'original' => $original,
            'output' => $fixtures . '/tmp.xml',
            'originalContent' => $originalContent,
        ];
    }
}
