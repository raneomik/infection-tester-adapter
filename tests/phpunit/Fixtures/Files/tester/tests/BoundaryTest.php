<?php

declare(strict_types=1);

namespace App\Tester\Tests;

use Tester\Assert;

class BoundaryTest
{
    public function testThree(): void
    {
        Assert::true(true);
        Assert::true(true);
        Assert::true(true);
    }

    public function testTen(): void
    {
        Assert::true(true);
        Assert::true(true);
        Assert::true(true);
        Assert::true(true);
        Assert::true(true);
        Assert::true(true);
        Assert::true(true);
        Assert::true(true);
        Assert::true(true);
        Assert::true(true);
    }
}
