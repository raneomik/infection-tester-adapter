<?php

namespace App\Uncovered;

use function sprintf;

function formatName(string $firstName, string $lastName): string
{
    if (empty($firstName) && empty($lastName)) {
        return 'Anonymous';
    }

    if (empty($firstName)) {
        return $lastName;
    }

    if (empty($lastName)) {
        return $firstName;
    }

    return sprintf('%s %s', $firstName, $lastName);
}
