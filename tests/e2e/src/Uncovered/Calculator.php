<?php

namespace App\Uncovered;

use InvalidArgumentException;

class Calculator
{
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }

    public function subtract(int $a, int $b): int
    {
        return $a - $b;
    }

    public function multiply(int $a, int $b): int
    {
        return $a * $b;
    }

    public function divide(int $a, int $b): float
    {
        if (0 === $b) {
            throw new InvalidArgumentException('Division by zero');
        }

        return $a / $b;
    }

    public function isPositive(int $number): bool
    {
        return 0 < $number;
    }

    public function absolute(int $number): int
    {
        return 0 > $number ? -$number : $number;
    }
}
