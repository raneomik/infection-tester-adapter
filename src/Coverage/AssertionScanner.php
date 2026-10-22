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

use function array_pop;
use function count;
use function is_array;
use function preg_match;
use function str_contains;
use function str_ends_with;
use const T_COMMENT;
use const T_CURLY_OPEN;
use const T_DOC_COMMENT;
use const T_DOLLAR_OPEN_CURLY_BRACES;
use const T_FUNCTION;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_OBJECT_OPERATOR;
use const T_STRING;
use const T_WHITESPACE;
use function token_get_all;

/**
 * "*Assert" occurrences scanner in file and method using php tokens
 *
 * @internal
 */
final class AssertionScanner
{
    private const array IGNORABLE = [T_WHITESPACE => true, T_COMMENT => true, T_DOC_COMMENT => true];

    private const string IDENTIFIER = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/';

    private const string EXPECTATION = '/^(?:expect|deny)[A-Z]/';

    public readonly int $tokenSize;

    private int $depth = 0;

    private int $currentRecord = 0;

    private ?string $pending = null;

    /** @var list<array{string, int, int}> */
    private array $open = [];

    /** @var array<string, int> */
    private array $methods = [];

    /**
     * @param list<array{int, string, int}|string> $tokens
     */
    private function __construct(
        private readonly array $tokens,
        private readonly bool $hasHttpAssert,
    ) {
        $this->tokenSize = count($this->tokens);
    }

    /**
     * @return array<string, int>
     */
    public static function scan(string $content): array
    {
        $tokens = token_get_all($content);

        return (new self($tokens, str_contains($content, 'HttpAssert')))->run();
    }

    /**
     * @return array<string, int>
     */
    private function run(): array
    {
        foreach ($this->tokens as $i => $token) {
            if (!is_array($token)) {
                match ($token) {
                    '{' => $this->openBrace(),
                    '}' => $this->closeBrace(),
                    ';' => $this->endDeclaration(),
                    default => null,
                };

                continue;
            }

            $hit = match ($token[0]) {
                T_STRING => 'Assert' === $token[1] && $this->isStaticCall($i),
                T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED => str_ends_with($token[1], '\\Assert') && $this->isStaticCall($i),
                T_OBJECT_OPERATOR => $this->hasHttpAssert && $this->isMemberCall($i, self::EXPECTATION),
                T_FUNCTION => $this->startDeclaration($i),
                T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES => $this->enter(),
                default => false,
            };

            if ($hit) {
                $this->record();
            }
        }

        return $this->methods + ['total' => $this->currentRecord];
    }

    private function openBrace(): void
    {
        if (null !== $this->pending) {
            $this->open[] = [$this->pending, $this->depth, 0];
            $this->pending = null;
        }

        ++$this->depth;
    }

    private function closeBrace(): void
    {
        --$this->depth;
        $last = count($this->open) - 1;

        if (0 <= $last && $this->open[$last][1] === $this->depth) {
            // @phpstan-ignore-next-line offsetAccess.nonArray
            [$name, , $assertions] = array_pop($this->open);
            $this->methods[$name] ??= $assertions;
        }
    }

    private function endDeclaration(): void
    {
        if (null !== $this->pending) {
            $this->methods[$this->pending] ??= 0;
            $this->pending = null;
        }
    }

    private function enter(): bool
    {
        ++$this->depth;

        return false;
    }

    private function startDeclaration(int $i): bool
    {
        $name = $this->next($i);

        if ('&' === $this->text($name)) {
            $name = $this->next($name);
        }

        $this->pending = 1 === preg_match(self::IDENTIFIER, $this->text($name))
        && '(' === $this->text($this->next($name))
            ? $this->text($name)
            : null;

        return false;
    }

    private function record(): void
    {
        ++$this->currentRecord;

        for ($k = count($this->open); 0 < $k--;) {
            ++$this->open[$k][2];
        }
    }

    private function isStaticCall(int $classToken): bool
    {
        $operator = $this->next($classToken);

        return '::' === $this->text($operator) && $this->isMemberCall($operator);
    }

    private function isMemberCall(int $operator, string $namePattern = self::IDENTIFIER): bool
    {
        $name = $this->next($operator);

        return 1 === preg_match($namePattern, $this->text($name))
            && '(' === $this->text($this->next($name));
    }

    private function next(int $i): int
    {
        while (++$i < $this->tokenSize) {
            $token = $this->tokens[$i];

            if (!is_array($token) || !isset(self::IGNORABLE[$token[0]])) {
                return $i;
            }
        }

        return $this->tokenSize;
    }

    private function text(int $i): string
    {
        $token = $this->tokens[$i] ?? '';

        return is_array($token) ? $token[1] : $token;
    }
}
