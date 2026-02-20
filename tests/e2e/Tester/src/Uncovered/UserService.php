<?php

namespace App\Uncovered;

use function count;
use function sprintf;

class UserService
{
    use LoggerTrait;

    private array $users = [];

    public function addUser(string $name, string $email): bool
    {
        if (empty($name) || empty($email)) {
            $this->log('Failed to add user: empty name or email');

            return false;
        }

        if ($this->userExists($email)) {
            $this->log(sprintf('Failed to add user: email %s already exists', $email));

            return false;
        }

        $this->users[$email] = ['name' => $name, 'email' => $email];
        $this->log(sprintf('User %s added successfully', $name));

        return true;
    }

    public function removeUser(string $email): bool
    {
        if (!$this->userExists($email)) {
            $this->log(sprintf('Failed to remove user: email %s not found', $email));

            return false;
        }

        unset($this->users[$email]);
        $this->log(sprintf('User %s removed successfully', $email));

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
}
