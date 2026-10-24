<?php

declare(strict_types=1);

namespace App\Inner;

class InnerSourceClass
{
    public function sub(float $a, float $b): float
    {
        return $a - $b;
    }

    // tests check only one side of the boundary on purpose: an escaping mutant proves mutants really run
    // (a crashing mutant run counts as "killed", so an all-killed suite can't tell)
    public function isPositive(float $a): bool
    {
        return $a > 0;
    }
}
