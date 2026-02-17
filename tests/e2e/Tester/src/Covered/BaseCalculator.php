<?php
declare(strict_types=1);
namespace App\Covered;
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
        if ($b === 0) {
            throw new \InvalidArgumentException('Division by zero');
        }
        return $a / $b;
    }
    public function isPositive(int $value): bool
    {
        return $value >= 0;
    }
    public function getAbsolute(int $value): int
    {
        return abs($value);
    }
}
