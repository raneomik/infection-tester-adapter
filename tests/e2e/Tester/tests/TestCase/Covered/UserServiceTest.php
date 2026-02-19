<?php

namespace App\Tests\TestCase\Covered;

require __DIR__ . '/../../bootstrap.php';

use App\Covered\UserService;
use Tester\Assert;
use Tester\TestCase;

/**
 * @testCase
 */
class UserServiceTest extends TestCase
{
    public function testAddUser(): void
    {
        $service = new UserService();
        Assert::true($service->addUser('John Doe', 'john@example.com'));
        Assert::same(1, $service->getUserCount());
        Assert::true($service->hasLogs());
    }

    public function testAddUserWithEmptyName(): void
    {
        $service = new UserService();
        Assert::false($service->addUser('', 'john@example.com'));
        Assert::same(0, $service->getUserCount());
        Assert::contains('Failed to add user: empty name or email', $service->getLogs());
    }

    public function testAddUserWithEmptyEmail(): void
    {
        $service = new UserService();
        Assert::false($service->addUser('John Doe', ''));
        Assert::same(0, $service->getUserCount());
        Assert::contains('Failed to add user: empty name or email', $service->getLogs());

    }

    public function testAddDuplicateUser(): void
    {
        $service = new UserService();
        $service->addUser('John Doe', 'john@example.com');
        Assert::false($service->addUser('Jane Doe', 'john@example.com'));
        Assert::same(1, $service->getUserCount());
        Assert::contains('Failed to add user: email john@example.com already exists', $service->getLogs());
    }

    public function testRemoveUser(): void
    {
        $service = new UserService();
        $service->addUser('John Doe', 'john@example.com');
        Assert::true($service->removeUser('john@example.com'));
        Assert::same(0, $service->getUserCount());
        Assert::false($service->removeUser('john@example.com'));
        Assert::same(0, $service->getUserCount());
        Assert::contains('Failed to remove user: email john@example.com not found', $service->getLogs());
    }

    public function testRemoveNonExistentUser(): void
    {
        $service = new UserService();
        Assert::false($service->removeUser('john@example.com'));
    }

    public function testGetUser(): void
    {
        $service = new UserService();
        $service->addUser('John Doe', 'john@example.com');
        $user = $service->getUser('john@example.com');
        Assert::type('array', $user);
        Assert::same('John Doe', $user['name']);
        Assert::same('john@example.com', $user['email']);
    }

    public function testGetNonExistentUser(): void
    {
        $service = new UserService();
        Assert::null($service->getUser('john@example.com'));
    }

    public function testUserExists(): void
    {
        $service = new UserService();
        $service->addUser('John Doe', 'john@example.com');
        Assert::true($service->userExists('john@example.com'));
        Assert::false($service->userExists('jane@example.com'));
    }

    public function testGetLogs(): void
    {
        $service = new UserService();
        $service->addUser('John Doe', 'john@example.com');
        $logs = $service->getLogs();
        Assert::type('array', $logs);
        Assert::count(1, $logs);
    }

    public function testClearLogs(): void
    {
        $service = new UserService();
        $service->addUser('John Doe', 'john@example.com');
        $service->clearLogs();
        Assert::false($service->hasLogs());
    }
}

(new UserServiceTest())->run();
