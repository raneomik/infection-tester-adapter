<?php

namespace App\Covered;

trait LoggerTrait
{
    private array $logs = [];
    protected function log(string $message): void
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
    protected function hasLogs(): bool
    {
        return count($this->logs) > 0;
    }
}
