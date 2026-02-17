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
 * Template for mutant test execution bootstrap.
 * Uses Infection's IncludeInterceptor to intercept file includes and inject mutations.
 */
final class MutationBootstrapTemplate
{
    /**
     * Generate the bootstrap content for mutant execution.
     *
     * @param string|null $originalBootstrap Path to original bootstrap file (if any)
     * @param string $autoloadPath Path to vendor/autoload.php
     * @param string $originalFilePath Path to original source file
     * @param string $mutatedFilePath Path to mutated source file
     */
    public static function generate(
        ?string $originalBootstrap,
        string $autoloadPath,
        string $originalFilePath,
        string $mutatedFilePath,
    ): string {
        return sprintf(<<<'PHP'
<?php
/**
 * Mutation bootstrap for Infection + Tester
 * Generated automatically - do not edit
 */

declare(strict_types=1);

// Load project autoloader (includes IncludeInterceptor)
require_once %s;

// Delegate setup to MutationBootstrapSetup class
\Raneomik\InfectionTestFramework\Tester\Script\MutationBootstrapSetup::run(
    %s,
    %s,
    %s
);

PHP,
            var_export($autoloadPath, true),
            var_export($originalBootstrap ?? '', true),
            var_export($originalFilePath, true),
            var_export($mutatedFilePath, true),
        );
    }
}
