<?php

namespace App\Covered;

use function count;

trait LoggerTrait
{
    private array $logs = [];

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

    private function log(string $message): void
    {
        $this->logs[] = $message;
    }
}
