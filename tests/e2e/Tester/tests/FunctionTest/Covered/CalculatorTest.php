<?php

declare(strict_types=1);

namespace App\Tests\FunctionTest\Covered;

require __DIR__ . '/../bootstrap.php';

use App\Covered\Calculator;
use Tester\Assert;

test('Addition Test', function () {
    $calculator = new Calculator();
    Assert::same(5, $calculator->add(2, 3));
    Assert::same(0, $calculator->add(-5, 5));
    Assert::same(-10, $calculator->add(-3, -7));
});

test('Substraction Test', function () {
    $calculator = new Calculator();
    Assert::same(1, $calculator->subtract(3, 2));
    Assert::same(-10, $calculator->subtract(-5, 5));
    Assert::same(4, $calculator->subtract(-3, -7));
});

test('Multiplication Test', function () {
    $calculator = new Calculator();
    Assert::same(6, $calculator->multiply(2, 3));
    Assert::same(-25, $calculator->multiply(-5, 5));
    Assert::same(21, $calculator->multiply(-3, -7));
    Assert::same(0, $calculator->multiply(5, 0));
});

test('Division Test', function () {
    $calculator = new Calculator();
    Assert::same(2.0, $calculator->divide(6, 3));
    Assert::same(-1.0, $calculator->divide(-5, 5));
    Assert::same(2.5, $calculator->divide(5, 2));
});

test('Positive number Test', function () {
    $calculator = new Calculator();
    Assert::true($calculator->isPositive(5));
    Assert::true($calculator->isPositive(0));
    Assert::false($calculator->isPositive(-5));
});

test('Absolute number Test', function () {
    $calculator = new Calculator();
    Assert::same(5, $calculator->getAbsolute(5));
    Assert::same(5, $calculator->getAbsolute(-5));
    Assert::same(0, $calculator->getAbsolute(0));
});

testException('Division by zero', function () {
    $calculator = new Calculator();
    $calculator->divide(5, 0);
},
    \InvalidArgumentException::class,
    'Division by zero',
);
