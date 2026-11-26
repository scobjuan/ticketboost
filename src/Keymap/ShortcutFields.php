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

use function Safe\preg_split;

/**
 * Reads the shortcut fields of the configuration forms (`keymap[action]` and, for personal
 * settings, `disabled[action]`).
 *
 * @phpstan-import-type Issue from KeymapValidator
 */
final class ShortcutFields
{
    /**
     * Turns the fields into an override layer: an empty field keeps the base binding, a field
     * equal to the base stores nothing and a disabled action stores an empty list.
     *
     * @param array<string, list<string>> $base     Action id to the bindings that apply without the layer.
     * @param list<string>                $disabled Actions switched off.
     *
     * @return array<array-key, list<string>>
     */
    public static function layer(mixed $fields, array $base, array $disabled = []): array
    {
        $layer = [];
        foreach ($disabled as $action) {
            if (($base[$action] ?? []) !== []) {
                $layer[$action] = [];
            }
        }

        $fields = is_array($fields) ? $fields : [];
        foreach ($fields as $action => $text) {
            if (!is_string($action) || !is_string($text) || in_array($action, $disabled, true)) {
                continue;
            }

            $bindings = array_values(array_filter(
                preg_split('/[\s,]+/', $text, -1, PREG_SPLIT_NO_EMPTY),
                is_string(...),
            ));
            if ($bindings === [] || $bindings === ($base[$action] ?? [])) {
                continue;
            }

            $layer[$action] = $bindings;
        }

        return $layer;
    }

    /**
     * The text to show again for each field that overrides the base.
     *
     * @param array<array-key, list<string>> $layer
     *
     * @return array<array-key, string> Action id to the bindings as text, `Ctrl+KeyK, KeyN`.
     */
    public static function text(array $layer): array
    {
        return array_map(static fn(array $bindings): string => implode(', ', $bindings), $layer);
    }

    /**
     * Names the bindings that do not parse, so a form can point at them.
     *
     * @param array<array-key, list<string>> $layer
     *
     * @return list<Issue>
     */
    public static function invalidIssues(array $layer): array
    {
        $issues = [];
        foreach ($layer as $action => $bindings) {
            foreach ($bindings as $binding) {
                if (KeymapValidator::normalize($binding) === null) {
                    $issues[] = ['code' => KeymapValidator::INVALID_BINDING, 'action' => (string) $action, 'binding' => $binding];
                }
            }
        }

        return $issues;
    }
}
