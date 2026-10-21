<?php

declare(strict_types=1);

namespace App\Tester\Tests;

use Tester\Assert;
use Tester\HttpAssert;

class MiscLargeTest
{
    public function testSomethingLarge(): void
    {
        Assert::true(true);
        Assert::false(false);
        Assert::same('test', 'test');
        Assert::contains('test', 'est');
        Assert::contains('test', ['test']);
        Assert::falsey([]);
        // Assert::same(4, 2+2); to ignore in assertion scan
        Assert::notSame(5, 2+2);
        Assert::null(null);
        Assert::type(\stdClass::class, (object) ['test' => 'test']);
        HttpAssert::fetch('http://example.com/')->expectCode(200);
        Assert::exception(
            fn() => throw new \RuntimeException('This test expects exception'),
            \RuntimeException::class,
        );
    }
}
