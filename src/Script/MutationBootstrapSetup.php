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

namespace Raneomik\InfectionTestFramework\Tester\Script;

use Infection\StreamWrapper\IncludeInterceptor;
use function is_file;

/**
 * Sets up the mutation environment by configuring the IncludeInterceptor.
 * This class is called from the generated mutation bootstrap script.
 *
 * The original bootstrap (e.g., tests/bootstrap.php) is loaded AFTER the interceptor
 * is configured, so that any files included by the bootstrap are properly intercepted.
 */
final readonly class MutationBootstrapSetup
{
    public function __construct(
        private ?string $originalBootstrap, // Path to tests/bootstrap.php (optional)
        private string $originalFilePath,   // Original source file to intercept
        private string $mutatedFilePath,    // Mutated file to inject
    ) {
    }

    /**
     * Setup the mutation interception.
     *
     * Order is important:
     * 1. Configure interceptor FIRST
     * 2. Load bootstrap AFTER (so its includes are intercepted)
     */
    private function setup(): void
    {
        $this->configureInterceptor();
        $this->loadOriginalBootstrap();
    }

    /**
     * Static entry point for the generated bootstrap script.
     */
    public static function run(
        ?string $originalBootstrap,
        string $originalFilePath,
        string $mutatedFilePath,
    ): void {
        (new self($originalBootstrap, $originalFilePath, $mutatedFilePath))->setup();
    }

    /**
     * Load the original test bootstrap if it exists.
     *
     * This is typically tests/bootstrap.php from the tested project.
     * It's loaded AFTER the interceptor is configured so that any files
     * included by the bootstrap are properly intercepted.
     *
     * Note: The bootstrap may already have been loaded by Tester,
     * but requiring it again is safe thanks to PHP's require_once.
     */
    private function loadOriginalBootstrap(): void
    {
        if (null === $this->originalBootstrap || !is_file($this->originalBootstrap)) {
            // Bootstrap not found or empty - this is OK as it might already be loaded by Tester
            return;
        }

        require_once $this->originalBootstrap;
    }

    /**
     * Configure and enable the IncludeInterceptor.
     */
    private function configureInterceptor(): void
    {
        IncludeInterceptor::intercept(
            $this->originalFilePath,
            $this->mutatedFilePath
        );

        IncludeInterceptor::enable();
    }
}
