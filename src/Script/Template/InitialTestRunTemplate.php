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
 * Generates PHP wrapper for initial test run execution.
 * Replaces bash wrapper scripts with PHP-based solution.
 */
final class InitialTestRunTemplate
{
    /**
     * Generate a PHP wrapper script for initial test run.
     *
     * @param string[] $commandParts Array of command parts (not escaped)
     * @param string $autoloadPath Path to the project autoload file
     * @param string $coverageFragmentDir Directory containing coverage fragments
     * @param string $tmpDir Temporary directory for output
     * @param string $tmpJunitPath Path to the junit.xml file
     *
     * @return string PHP wrapper script content
     */
    public static function generateWrapper(
        array $commandParts,
        string $autoloadPath,
        string $coverageFragmentDir,
        string $tmpDir,
        string $tmpJunitPath,
    ): string {
        return sprintf(<<<'PHP'
<?php
/**
 * Initial test run wrapper for Infection + Tester
 * Generated automatically - do not edit
 */

declare(strict_types=1);

// Load autoload to make classes available
require_once %s;

// Delegate to InitialTestRunner class for clean execution
exit(\Raneomik\InfectionTestFramework\Tester\Script\InitialTestRunner::execute(
    %s,
    %s,
    %s,
    %s
));

PHP,
            var_export($autoloadPath, true),
            var_export($commandParts, true),
            var_export($coverageFragmentDir, true),
            var_export($tmpDir, true),
            var_export($tmpJunitPath, true),
        );
    }
}
