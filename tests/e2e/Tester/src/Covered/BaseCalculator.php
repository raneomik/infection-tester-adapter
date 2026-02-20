<?php

declare(strict_types=1);

namespace App\Covered;

use function abs;
use InvalidArgumentException;

class BaseCalculator
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

    public function isPositive(int $value): bool
    {
        return 0 <= $value;
    }

    public function getAbsolute(int $value): int
    {
        return abs($value);
    }
}
