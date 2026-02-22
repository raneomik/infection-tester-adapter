<?php

declare(strict_types=1);

namespace App\Tester\Tests;

class TestCaseTest
{
    public function testFail(): void
    {
        Assert::fail('This test should fail');
    }

    public function testTrueFalse(): void
    {
        Assert::true(true);
        Assert::false(false);
    }
}
