<?php

/**
 * This code is licensed under the BSD 3-Clause License.
 *
 * Copyright (c) 2017, Maks Rafalko
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Raneomik\InfectionTestFramework\Tester\Script\Template;

use function sprintf;
use function var_export;

/**
 * Generates a PHP wrapper for mutant test execution.
 * killedBy tests correctly.
 */
final class MutationWrapperTemplate
{
    /**
     * @param string[] $commandParts
     */
    public static function generate(
        array $commandParts,
        string $autoloadPath,
    ): string {
        return sprintf(<<<'PHP'
<?php
/**
 * Mutant test run wrapper for Infection + Tester
 * Generated automatically - do not edit
 */

declare(strict_types=1);

require_once %s;

use Raneomik\InfectionTestFramework\Tester\Coverage\AssertionsCounter;
use Raneomik\InfectionTestFramework\Tester\Coverage\CoveringTestIdentifier;
use Symfony\Component\Process\Process;

$testOutput = '';
$assertCounter = AssertionsCounter::getInstance();
$testIdentifier = new CoveringTestIdentifier();

$process = new Process(%s);
$process->setTimeout(null);
$process->run(static function (string $type, string $buffer) use (&$testOutput): void {
    $testOutput .= $buffer;
});

$exitCode = $process->getExitCode() ?? 1;

if (0 !== $exitCode && 1 === preg_match(
    '/^not ok\s+(?P<filepath>\S+\.php)(?:\s+method=(?P<method>\w+))?/m',
    $testOutput, 
    $match,
)) {    
    $testFile   = $match['filepath'];
    $methodName = $match['method'] ?? 'test';

    $class = '';
    $content = @file_get_contents($testFile) ?: '';

    $namespace = '';
    if (preg_match('/^\s*namespace\s+([a-zA-Z0-9_\\\\]+)\s*;/m', $content, $nm)) {
        $namespace = $nm[1];
    }
    
    if (preg_match('/\bclass\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $cm)) {
        $class = $cm[1];
    }
        
    $assertCounts = $assertCounter->countAssertions($testFile, $content);
    $assertCountInTest = $assertCounts[$methodName] ?? $assertCounts['total'] ?? 0;
    $testOutput = "\n" . "Tests: 1, Assertions: " . $assertCountInTest . "\n\n" . $testOutput;
    
    $testOutput = "\n" . $testIdentifier->getTestId(
        $methodName,
        $class,
        $namespace,
        $testFile,
    ) . $testOutput;
}

fwrite(STDOUT, $testOutput);
exit($exitCode);

PHP,
            var_export($autoloadPath, true),
            var_export($commandParts, true),
        );
    }
}
