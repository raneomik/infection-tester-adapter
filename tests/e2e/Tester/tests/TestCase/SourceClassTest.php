<?php

namespace App\Tests\TestCase;

require __DIR__ . '/../bootstrap.php';

use App\SourceClass;
use Tester\Assert;
use Tester\TestCase;

/**
 * @testCase
 */
class SourceClassTest extends TestCase
{
    public function testAddition(): void
    {
        $source = new SourceClass();
        Assert::same(3.0, 3.0);
        Assert::same(3.0, $source->add(1, 2));
    }

    /**
     * @testCase
     */
    public function testFloatAddition(): void
    {
        $source = new SourceClass();
        Assert::same(0.3, round($source->add(0.1, 0.2), 1));
    }
}

(new SourceClassTest())->run();
