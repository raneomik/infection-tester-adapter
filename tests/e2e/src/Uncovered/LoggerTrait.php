<?php

namespace App\Uncovered;

use function count;

trait LoggerTrait
{
    private array $logs = [];

    public function log(string $message): void
    {
        $this->logs[] = $message;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function clearLogs(): void
    {
        $this->logs = [];
    }

    public function hasLogs(): bool
    {
        return 0 < count($this->logs);
    }
}
