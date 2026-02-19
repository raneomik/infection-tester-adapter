<?php

namespace App\Tests\TestCase\Covered;

require __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../../src/Covered/functions.php';

use Tester\Assert;
use Tester\TestCase;
use function App\Covered\formatName;

/**
 * @testCase
 */
class FormatNameFunctionTest extends TestCase
{
    public function testFormatFullName(): void
    {
        Assert::same('John Doe', formatName('John', 'Doe'));
    }

    public function testFormatFirstNameOnly(): void
    {
        Assert::same('John', formatName('John', ''));
    }

    public function testFormatLastNameOnly(): void
    {
        Assert::same('Doe', formatName('', 'Doe'));
    }

    public function testFormatEmptyNames(): void
    {
        Assert::same('Anonymous', formatName('', ''));
    }

    public function testFormatWithSpaces(): void
    {
        Assert::same('Mary Jane', formatName('Mary', 'Jane'));
    }
}

(new FormatNameFunctionTest())->run();
