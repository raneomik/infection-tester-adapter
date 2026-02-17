<?php

namespace App\Tests\TestCase\Inner;

require __DIR__ . '/../../bootstrap.php';

use App\Inner\InnerSourceClass;
use Tester\Assert;
use Tester\TestCase;

/**
 * @testCase
 */
class InnerSourceClassTest extends TestCase
{
    public function testSubtraction(): void
    {
        $source = new InnerSourceClass();
        Assert::same(-1.23, $source->sub(1, 2.23));
    }
}
(new InnerSourceClassTest())->run();
