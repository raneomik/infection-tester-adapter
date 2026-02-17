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

namespace Raneomik\InfectionTestFramework\Tester\Coverage;

use function chmod;
use function dirname;
use function file_put_contents;
use function is_dir;
use function is_file;
use function mkdir;
use Raneomik\InfectionTestFramework\Tester\Script\Template\AutoPrependTemplate;
use function rtrim;

/**
 * Generates coverage_prepend scripts for coverage collection.
 */
final class CoverageScriptGenerator
{
    /**
     * Prepare the coverage_prepend script for coverage collection.
     *
     * @param string $projectDir Root directory of the project
     * @param string $tmpDir Temporary directory for generated scripts
     * @param string[] $srcDirs Absolute paths of source directories to cover
     * @param string $fragmentDir Directory where coverage fragments will be written
     *
     * @return array{script: string, autoload: string|null}
     */
    public static function generate(
        string $projectDir,
        string $tmpDir,
        array $srcDirs,
        string $fragmentDir,
    ): array {
        self::ensureDir($tmpDir);
        self::ensureDir($fragmentDir);

        $autoload = self::findAutoload($projectDir);
        $scriptPath = $tmpDir . '/coverage_prepend.php';

        self::writeScript($scriptPath, $autoload, $fragmentDir, $srcDirs);

        return [
            'script' => $scriptPath,
            'autoload' => $autoload,
        ];
    }

    /**
     * Find the Composer autoload.php file.
     * Walks up the directory tree until finding vendor/autoload.php.
     */
    private static function findAutoload(string $projectDir): ?string
    {
        $dir = rtrim($projectDir, '/');

        while ('' !== $dir) {
            $candidate = $dir . '/vendor/autoload.php';

            if (is_file($candidate)) {
                return $candidate;
            }

            $parent = dirname($dir);

            if ($parent === $dir) {
                break;
            }

            $dir = $parent;
        }

        return null;
    }

    /**
     * Ensure directories exist.
     */
    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    /**
     * Write the auto_prepend_file script.
     *
     * @param string[] $srcDirs
     */
    private static function writeScript(
        string $scriptPath,
        ?string $autoload,
        string $fragmentDir,
        array $srcDirs,
    ): void {
        $content = AutoPrependTemplate::build($autoload, $fragmentDir, $srcDirs);

        file_put_contents($scriptPath, $content);
        @chmod($scriptPath, 0644);
    }
}
