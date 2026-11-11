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

namespace Raneomik\Tests\InfectionTestFramework\Tester\Config;

use function file_put_contents;
use function mkdir;
use PHPUnit\Framework\Attributes\Group;
use Raneomik\InfectionTestFramework\Tester\Config\MutationConfigBuilder;
use Raneomik\Tests\InfectionTestFramework\Tester\FileSystem\FileSystemTestCase;
use function touch;

#[Group('unit')]
final class MutationConfigBuilderTest extends FileSystemTestCase
{
    public function test_it_prefers_the_project_autoload(): void
    {
        mkdir($this->tmp . '/project/vendor', recursive: true);
        touch($autoload = $this->tmp . '/project/vendor/autoload.php');

        $builder = new MutationConfigBuilder($this->tmp, $this->tmp . '/project');

        self::assertFileContains("'" . $autoload . "'", $builder->createMutationBootstrap('hash', '/original.php', '/mutated.php'));
        self::assertFileContains("'" . $autoload . "'", $builder->createMutantSetupScript('hash'));
    }

    public function test_it_reuses_existing_scripts(): void
    {
        mkdir($this->tmp . '/mutant');
        file_put_contents($bootstrap = $this->tmp . '/mutant/bootstrap-hash.php', 'existing bootstrap');
        file_put_contents($setup = $this->tmp . '/mutant/setup-hash.php', 'existing setup');

        $builder = new MutationConfigBuilder($this->tmp, $this->tmp);

        self::assertSame($bootstrap, $builder->createMutationBootstrap('hash', '/original.php', '/mutated.php'));
        self::assertSame($setup, $builder->createMutantSetupScript('hash'));
        self::assertStringEqualsFile($bootstrap, 'existing bootstrap');
        self::assertStringEqualsFile($setup, 'existing setup');
    }
}
