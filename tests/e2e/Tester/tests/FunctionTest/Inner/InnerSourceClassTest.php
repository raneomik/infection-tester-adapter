<?php

declare(strict_types=1);

namespace App\Tests\FunctionTest\Inner;

require __DIR__ . '/../bootstrap.php';

use App\Inner\InnerSourceClass;
use Tester\Assert;

test('Subtraction Test', function () {
    $source = new InnerSourceClass();
    Assert::same(-1.23, $source->sub(1, 2.23));
});
