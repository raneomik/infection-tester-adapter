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

namespace Raneomik\InfectionTestFramework\Tester\Config;

use function is_file;
use RuntimeException;
use function sprintf;

/**
 * Factory for creating MutationConfigBuilder instances.
 * Uses Tester conventions: tests/ directory and tests/bootstrap.php
 */
final readonly class MutationConfigBuilderFactory
{
    private const DEFAULT_BOOSTRAP_FILE = 'tests/bootstrap.php';

    public function __construct(
        private string $tmpDir,
        private string $projectDir,
    ) {
    }

    /**
     * Create a MutationConfigBuilder with conventional bootstrap path.
     */
    public function create(): MutationConfigBuilder
    {
        return new MutationConfigBuilder(
            $this->tmpDir,
            $this->projectDir,
            $this->resolveBootstrapPath(),
        );
    }

    private function resolveBootstrapPath(): string
    {
        // Tester convention: tests/bootstrap.php
        $bootstrapPath = sprintf(
            '%s/%s',
            $this->projectDir,
            self::DEFAULT_BOOSTRAP_FILE
        );

        if (is_file($bootstrapPath)) {
            return $bootstrapPath;
        }

        throw new RuntimeException(sprintf('Bootstrap file "%s" not found.', $bootstrapPath));
    }
}
