<?php

namespace App\Tests\TestCase\Covered;

require __DIR__ . '/../../bootstrap.php';

use Tester\Assert;
use Tester\TestCase;
use App\Covered\Calculator;

/**
 * @testCase
 */
class CalculatorTest extends TestCase
{
    public function testAddition(): void
    {
        $calculator = new Calculator();
        Assert::same(5, $calculator->add(2, 3));
        Assert::same(0, $calculator->add(-5, 5));
        Assert::same(-10, $calculator->add(-3, -7));
    }

    public function testSubtraction(): void
    {
        $calculator = new Calculator();
        Assert::same(1, $calculator->subtract(3, 2));
        Assert::same(-10, $calculator->subtract(-5, 5));
        Assert::same(4, $calculator->subtract(-3, -7));
    }

    public function testMultiplication(): void
    {
        $calculator = new Calculator();
        Assert::same(6, $calculator->multiply(2, 3));
        Assert::same(-25, $calculator->multiply(-5, 5));
        Assert::same(21, $calculator->multiply(-3, -7));
        Assert::same(0, $calculator->multiply(5, 0));
    }

    public function testDivision(): void
    {
        $calculator = new Calculator();
        Assert::same(2.0, $calculator->divide(6, 3));
        Assert::same(-1.0, $calculator->divide(-5, 5));
        Assert::same(2.5, $calculator->divide(5, 2));
    }

    public function testDivisionByZero(): void
    {
        $calculator = new Calculator();
        Assert::exception(fn() => $calculator->divide(5, 0), \InvalidArgumentException::class, 'Division by zero');
    }

    public function testIsPositive(): void
    {
        $calculator = new Calculator();
        Assert::true($calculator->isPositive(5));
        Assert::true($calculator->isPositive(0));
        Assert::false($calculator->isPositive(-5));
    }

    public function testAbsolute(): void
    {
        $calculator = new Calculator();
        Assert::same(5, $calculator->getAbsolute(5));
        Assert::same(5, $calculator->getAbsolute(-5));
        Assert::same(0, $calculator->getAbsolute(0));
    }
}

(new CalculatorTest())->run();
