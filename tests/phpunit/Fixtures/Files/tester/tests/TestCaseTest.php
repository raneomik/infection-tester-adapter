<?php

declare(strict_types=1);

namespace App\Tester\Tests;

use Tester\Assert;

class TestCaseTest
{
    public function testFail(): void
    {
        Assert::fail('This test should fail', true);
    }

    public function testTrueFalseSame(): void
    {
        Assert::true(true);
        Assert::false(false);
        Assert::same(false, false);
        Assert::notSame(true, false);
    }

    public function testString(): void
    {
        Assert::type('string', 'string');
    }

    public function testException(): void
    {
        Assert::exception(
            fn() => throw new \RuntimeException('This test expects exception'),
            \RuntimeException::class,
        );
    }
}
