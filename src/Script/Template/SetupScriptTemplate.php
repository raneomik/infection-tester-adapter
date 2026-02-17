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
 * Template for Tester --setup script.
 * This script is loaded by Tester Runner to configure coverage collection.
 */
final class SetupScriptTemplate
{
    /**
     * Generate the setup script with actual values.
     *
     * @param string $autoloadPath Path to the vendor autoload file
     * @param string $prependFile Path to the coverage prepend script
     * @param string $srcDir Source directory for PCOV filtering
     *
     * @return string PHP script with substituted values
     */
    public static function build(string $autoloadPath, string $prependFile, string $srcDir): string
    {
        return sprintf(<<<'PHP'
<?php
/**
 * Tester setup script for Infection coverage collection
 * Generated automatically - do not edit
 */

declare(strict_types=1);

// Load autoload first
require_once %s;

// Configure the Tester Runner
// Note: $runner is available via use() in the closure from CliTester
if (isset($runner) && $runner instanceof \Tester\Runner\Runner) {
    \Raneomik\InfectionTestFramework\Tester\Script\TesterJobSetup::configure(
        $runner,
        %s,
        %s,
    );
}

PHP,
            var_export($autoloadPath, true),
            var_export($prependFile, true),
            var_export($srcDir, true),
        );
    }
}
