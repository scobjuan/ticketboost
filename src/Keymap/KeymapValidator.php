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

namespace GlpiPlugin\Ticketboost\Keymap;

use function Safe\preg_match;

/**
 * Validates and normalizes key bindings (ADR 0017).
 *
 * A binding is `[Ctrl+][Alt+][Shift+][Meta+]Key`, where Key is a `KeyboardEvent.code`
 * from a fixed list or a single printable character. This class mirrors
 * `frontend/src/keymap/binding.ts` and `validator.ts`; both are tested against
 * `shared/keymap-cases.json`.
 *
 * @phpstan-type Issue array{code: string, action: string, binding?: string, with?: list<string>}
 */
final class KeymapValidator
{
    public const UNKNOWN_ACTION = 'unknown_action';

    public const INVALID_BINDING = 'invalid_binding';

    public const RESERVED_BINDING = 'reserved_binding';

    public const CONFLICT = 'conflict';

    /**
     * Chords that cannot be used: browsers never deliver them, GLPI binds them natively,
     * or the panel needs them.
     */
    public const RESERVED = [
        'Ctrl+KeyW',
        'Ctrl+KeyT',
        'Ctrl+KeyN',
        'Ctrl+Tab',
        'Ctrl+Shift+KeyT',
        'Ctrl+Shift+KeyN',
        'Ctrl+Shift+KeyW',
        'Ctrl+Alt+KeyG',
        'Ctrl+Alt+KeyE',
        'Ctrl+ArrowLeft',
        'Ctrl+ArrowRight',
        'Escape',
        'Tab',
        'Shift+Tab',
        'Backspace',
    ];

    private const MODIFIERS = ['Ctrl', 'Alt', 'Shift', 'Meta'];

    private const CODE_PATTERN = '/^(Key[A-Z]|Digit\d|Numpad\d|F([1-9]|1[0-2])|Arrow(Up|Down|Left|Right)|Enter|Space'
        . '|Backspace|Tab|Escape|Delete|Insert|Home|End|PageUp|PageDown|Minus|Equal|Comma|Period|Slash|Semicolon'
        . '|Quote|Backquote|Backslash|BracketLeft|BracketRight)$/';

    /**
     * Normalizes a binding so equal bindings compare equal.
     *
     * @return string|null The canonical text, or null when the binding is invalid.
     */
    public static function normalize(string $text): ?string
    {
        $parts = explode('+', $text);
        $key   = array_pop($parts);
        if ($key === '' && $parts !== [] && end($parts) === '') {
            array_pop($parts);
            $key = '+';
        }

        $flags = array_fill_keys(self::MODIFIERS, false);
        foreach ($parts as $part) {
            if (!isset($flags[$part]) || $flags[$part]) {
                return null;
            }

            $flags[$part] = true;
        }

        if (preg_match(self::CODE_PATTERN, $key) === 1) {
            $name = $key;
        } elseif (preg_match('/^\S$/u', $key) === 1) {
            $name           = $key;
            $flags['Shift'] = false;
        } else {
            return null;
        }

        $modifiers = array_keys(array_filter($flags));

        return implode('+', [...$modifiers, $name]);
    }

    /**
     * Tells whether a normalized binding is reserved.
     */
    public static function isReserved(string $normalized): bool
    {
        return in_array($normalized, self::RESERVED, true);
    }

    /**
     * Validates a keymap: known actions, parseable bindings, no reserved chords and no
     * binding shared by two actions or listed twice.
     *
     * @param array<string, mixed> $keymap Action id to bindings; untrusted input.
     *
     * @return list<Issue> Every issue found; empty when the keymap is valid.
     */
    public static function validate(array $keymap): array
    {
        $issues = [];
        /** @var array<string, list<string>> $owners */
        $owners = [];

        foreach ($keymap as $action => $bindings) {
            $action = (string) $action;
            if (!ActionCatalog::exists($action)) {
                $issues[] = ['code' => self::UNKNOWN_ACTION, 'action' => $action];
                continue;
            }

            foreach (is_array($bindings) ? $bindings : [] as $text) {
                $normalized = is_string($text) ? self::normalize($text) : null;
                if ($normalized === null) {
                    $issues[] = [
                        'code'    => self::INVALID_BINDING,
                        'action'  => $action,
                        'binding' => is_string($text) ? $text : '',
                    ];
                    continue;
                }

                if (self::isReserved($normalized)) {
                    $issues[] = ['code' => self::RESERVED_BINDING, 'action' => $action, 'binding' => $normalized];
                    continue;
                }

                $owners[$normalized][] = $action;
            }
        }

        foreach ($owners as $binding => $actions) {
            if (count($actions) < 2) {
                continue;
            }

            foreach (array_values(array_unique($actions)) as $action) {
                $others   = array_values(array_unique(array_filter(
                    $actions,
                    static fn(string $other): bool => $other !== $action,
                )));
                $issues[] = [
                    'code'    => self::CONFLICT,
                    'action'  => $action,
                    'binding' => (string) $binding,
                    'with'    => $others === [] ? [$action] : $others,
                ];
            }
        }

        return $issues;
    }
}
