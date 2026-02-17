<?php

declare(strict_types=1);

namespace App\Covered;

class UserService
{
    private array $users = [];
    private array $logs = [];

    private function log(string $message): void
    {
        $this->logs[] = $message;
    }

    public function addUser(string $name, string $email): bool
    {
        if (empty($name) || empty($email)) {
            $this->log('Failed to add user: empty name or email');
            return false;
        }
        if ($this->userExists($email)) {
            $this->log("Failed to add user: email {$email} already exists");
            return false;
        }
        $this->users[$email] = ['name' => $name, 'email' => $email];
        $this->log("User {$name} added successfully");
        return true;
    }

    public function removeUser(string $email): bool
    {
        if (!$this->userExists($email)) {
            $this->log("Failed to remove user: email {$email} not found");
            return false;
        }

        unset($this->users[$email]);


        return true;
    }

    public function getUser(string $email): ?array
    {
        return $this->users[$email] ?? null;
    }

    public function userExists(string $email): bool
    {
        return isset($this->users[$email]);
    }

    public function getUserCount(): int
    {
        return count($this->users);
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function hasLogs(): bool
    {
        return [] !== $this->logs;
    }

    public function clearLogs(): void
    {
        $this->logs = [];
    }
}
