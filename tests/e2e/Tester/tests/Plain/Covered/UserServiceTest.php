<?php

declare(strict_types=1);

namespace App\Tests\Plain\Covered;

require __DIR__ . '/../../bootstrap.php';

use App\Covered\UserService;
use Tester\Assert;
use Tester\TestCase;

$service = new UserService();
Assert::true($service->addUser('John Doe', 'john@example.com'));
Assert::same(1, $service->getUserCount());
Assert::true($service->hasLogs());
Assert::true($service->userExists('john@example.com'));
Assert::false($service->userExists('jane@example.com'));

Assert::false($service->addUser('', 'john@example.com'));
Assert::same(1, $service->getUserCount());
Assert::contains('Failed to add user: empty name or email', $service->getLogs());

Assert::false($service->addUser('John Doe', ''));
Assert::same(1, $service->getUserCount());

Assert::false($service->addUser('Jane Doe', 'john@example.com'));
Assert::same(1, $service->getUserCount());
Assert::contains('Failed to add user: email john@example.com already exists', $service->getLogs());

Assert::true($service->removeUser('john@example.com'));
Assert::same(0, $service->getUserCount());
Assert::false($service->removeUser('john@example.com'));
Assert::same(0, $service->getUserCount());
Assert::contains('Failed to remove user: email john@example.com not found', $service->getLogs());

$service->addUser('John Doe', 'john@example.com');
$user = $service->getUser('john@example.com');
Assert::type('array', $user);
Assert::same('John Doe', $user['name']);
Assert::same('john@example.com', $user['email']);

Assert::true($service->removeUser('john@example.com'));
Assert::null($service->getUser('john@example.com'));

$service->clearLogs();
$service->addUser('John Doe', 'john@example.com');
$logs = $service->getLogs();
Assert::type('array', $logs);
Assert::count(1, $logs);

$service->addUser('John Doe', 'john@example.com');
$service->clearLogs();
Assert::false($service->hasLogs());
