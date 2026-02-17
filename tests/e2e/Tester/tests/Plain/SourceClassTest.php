<?php

declare(strict_types=1);

namespace App\Tests\Plain;

require __DIR__ . '/../bootstrap.php';

use App\SourceClass;
use Tester\Assert;

$source = new SourceClass();
Assert::same(3.0, 3.0);
Assert::same(3.0, $source->add(1, 2));
Assert::same(0.3, round($source->add(0.1, 0.2), 1));

