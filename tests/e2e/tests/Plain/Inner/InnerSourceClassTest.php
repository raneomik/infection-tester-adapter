<?php

declare(strict_types=1);

namespace App\Tests\Plain\Inner;

require __DIR__ . '/../../bootstrap.php';

use App\Inner\InnerSourceClass;
use Tester\Assert;

$source = new InnerSourceClass();
Assert::same(-1.23, $source->sub(1, 2.23));
