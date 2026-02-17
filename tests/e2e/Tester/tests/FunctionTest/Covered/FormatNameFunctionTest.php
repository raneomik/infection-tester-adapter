<?php

declare(strict_types=1);

namespace App\Tests\FunctionTest\Covered;

require __DIR__ . '/../bootstrap.php';

use Tester\Assert;
use function App\Covered\formatName;

setUp(function () {
    require_once __DIR__ . '/../../../src/Covered/functions.php';
});

test('Format name test', function () {
    Assert::same('John Doe', formatName('John', 'Doe'));
    Assert::same('John', formatName('John', ''));
    Assert::same('Doe', formatName('', 'Doe'));
    Assert::same('Anonymous', formatName('', ''));
    Assert::same('Mary Jane', formatName('Mary', 'Jane'));
});

