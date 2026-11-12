<?php

/**
 * -------------------------------------------------------------------------
 * TicketBoost plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of TicketBoost.
 *
 * TicketBoost is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * TicketBoost is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with TicketBoost. If not, see <https://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2026 by Juan A. Escobar.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/scobjuan/ticketboost
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Ticketboost\Tests\Unit\Keymap;

use GlpiPlugin\Ticketboost\Keymap\ActionCatalog;
use GlpiPlugin\Ticketboost\Keymap\KeymapValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Runs the cases shared with the TypeScript tests, so both validators agree (ADR 0017).
 */
final class KeymapValidatorTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function cases(): array
    {
        $json = file_get_contents(__DIR__ . '/../../../shared/keymap-cases.json');
        self::assertIsString($json);
        $cases = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($cases);

        return $cases;
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizeProvider(): iterable
    {
        foreach (self::cases()['normalize'] as [$input, $expected]) {
            yield $input => [$input, $expected];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidProvider(): iterable
    {
        foreach (self::cases()['invalid'] as $input) {
            yield var_export($input, true) => [$input];
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<array<string, mixed>>}>
     */
    public static function validateProvider(): iterable
    {
        foreach (self::cases()['validate'] as $case) {
            yield $case['name'] => [$case['keymap'], $case['issues']];
        }
    }

    #[DataProvider('normalizeProvider')]
    public function testNormalize(string $input, string $expected): void
    {
        $this->assertSame($expected, KeymapValidator::normalize($input));
    }

    #[DataProvider('invalidProvider')]
    public function testNormalizeRejectsInvalidBindings(string $input): void
    {
        $this->assertNull(KeymapValidator::normalize($input));
    }

    #[DataProvider('validateProvider')]
    public function testValidate(array $keymap, array $issues): void
    {
        $this->assertSame($issues, KeymapValidator::validate($keymap));
    }

    public function testReservedListMatchesTheSharedCases(): void
    {
        $this->assertSame(self::cases()['reserved'], KeymapValidator::RESERVED);
    }

    public function testEveryReservedBindingIsRejected(): void
    {
        foreach (KeymapValidator::RESERVED as $binding) {
            $this->assertTrue(KeymapValidator::isReserved($binding), $binding);
            $this->assertSame(
                [['code' => 'reserved_binding', 'action' => 'next', 'binding' => $binding]],
                KeymapValidator::validate(['next' => [$binding]]),
            );
        }
    }

    public function testDefaultKeymapMatchesTheSharedCasesAndIsValid(): void
    {
        $this->assertSame(self::cases()['defaults'], ActionCatalog::defaultKeymap());
        $this->assertSame([], KeymapValidator::validate(ActionCatalog::defaultKeymap()));
    }

    public function testNonListBindingsAndNonStringEntriesAreInvalid(): void
    {
        $this->assertSame(
            [['code' => 'invalid_binding', 'action' => 'next', 'binding' => '']],
            KeymapValidator::validate(['next' => [42]]),
        );
        $this->assertSame([], KeymapValidator::validate(['next' => 'KeyN']));
    }
}
