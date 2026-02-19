<?php

namespace App\Covered;

trait LoggerTrait
{
    private array $logs = [];

    private function log(string $message): void
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
        return count($this->logs) > 0;
    }
}
