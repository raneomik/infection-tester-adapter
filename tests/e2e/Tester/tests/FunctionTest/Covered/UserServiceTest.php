<?php

declare(strict_types=1);

namespace App\Tests\FunctionTest\Covered;

require __DIR__ . '/../bootstrap.php';

use App\Covered\UserService;
use Tester\Assert;

test('Add User Test', function () {
    $service = new UserService();
    Assert::true($service->addUser('John Doe', 'john@example.com'));
    Assert::same(1, $service->getUserCount());
    Assert::true($service->hasLogs());
});

test('Cannot add nameless user', function () {
    $service = new UserService();
    Assert::false($service->addUser('', 'john@example.com'));
    Assert::same(0, $service->getUserCount());
    Assert::contains('Failed to add user: empty name or email', $service->getLogs());
});

test('Cannot add emailless user', function () {
    $service = new UserService();
    Assert::false($service->addUser('John Doe', ''));
    Assert::same(0, $service->getUserCount());
});

test('Cannot add duplicate user', function () {
    $service = new UserService();
    $service->addUser('John Doe', 'john@example.com');
    Assert::false($service->addUser('Jane Doe', 'john@example.com'));
    Assert::same(1, $service->getUserCount());
    Assert::contains('Failed to add user: email john@example.com already exists', $service->getLogs());
});

test('Remove User Test', function () {
    $service = new UserService();
    $service->addUser('John Doe', 'john@example.com');
    Assert::true($service->removeUser('john@example.com'));
    Assert::same(0, $service->getUserCount());
    Assert::false($service->removeUser('john@example.com'));
    Assert::same(0, $service->getUserCount());
    Assert::contains('Failed to remove user: email john@example.com not found', $service->getLogs());
});

test('Remove inexistent User Test', function () {
    $service = new UserService();
    Assert::false($service->removeUser('john@example.com'));
});

test('Get user test', function () {
    $service = new UserService();
    $service->addUser('John Doe', 'john@example.com');

    $user = $service->getUser('john@example.com');
    Assert::type('array', $user);
    Assert::same('John Doe', $user['name']);
    Assert::same('john@example.com', $user['email']);
});

test('Get inexistent user test', function () {
    $service = new UserService();
    Assert::null($service->getUser('john@example.com'));
});

test('User existence check test', function () {
    $service = new UserService();
    $service->addUser('John Doe', 'john@example.com');
    Assert::true($service->userExists('john@example.com'));
    Assert::false($service->userExists('jane@example.com'));
});

test('Get logs test', function () {
    $service = new UserService();
    $service->addUser('John Doe', 'john@example.com');

    $logs = $service->getLogs();
    Assert::type('array', $logs);
    Assert::count(1, $logs);
});

test('Clear log test', function () {
    $service = new UserService();
    $service->addUser('John Doe', 'john@example.com');
    $service->clearLogs();
    Assert::false($service->hasLogs());
});
