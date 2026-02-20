<?php

declare(strict_types=1);

namespace App\Tests\FunctionTest;

require __DIR__ . '/bootstrap.php';

use App\SourceClass;
use function round;
use Tester\Assert;

test('Addition Test', function (): void {
    $source = new SourceClass();
    Assert::same(3.0, 3.0);
    Assert::same(3.0, $source->add(1, 2));
});

test('Float addition test', function (): void {
    $source = new SourceClass();
    Assert::same(0.3, round($source->add(0.1, 0.2), 1));
});
