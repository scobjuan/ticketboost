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

/**
 * Computes the effective keymap from the defaults, the admin overrides and the user
 * overrides.
 */
final class KeymapResolver
{
    /**
     * Applies each layer on top of the previous one. An override replaces the whole
     * binding list of its action; an empty list disables the action. A layer that would
     * leave the keymap with any issue (unknown action, invalid or reserved binding,
     * conflict) is skipped entirely, so a bad stored preference can never break the panel.
     *
     * @param array<array-key, mixed> $admin Admin overrides.
     * @param array<array-key, mixed> $user  User overrides.
     *
     * @return array<string, list<string>> Action id to canonical bindings.
     */
    public static function effective(array $admin, array $user): array
    {
        $keymap = ActionCatalog::defaultKeymap();
        foreach ([$admin, $user] as $layer) {
            $candidate = self::apply($keymap, $layer);
            if ($candidate !== null && KeymapValidator::validate($candidate) === []) {
                $keymap = $candidate;
            }
        }

        return $keymap;
    }

    /**
     * @param array<string, list<string>> $keymap
     * @param array<array-key, mixed>     $layer
     *
     * @return array<string, list<string>>|null Null when the layer is not a map of lists of strings.
     */
    private static function apply(array $keymap, array $layer): ?array
    {
        foreach ($layer as $action => $bindings) {
            if (!is_string($action) || !is_array($bindings)) {
                return null;
            }

            $normalized = [];
            foreach ($bindings as $text) {
                $binding = is_string($text) ? KeymapValidator::normalize($text) : null;
                if ($binding === null) {
                    return null;
                }

                $normalized[] = $binding;
            }

            $keymap[$action] = $normalized;
        }

        return $keymap;
    }
}
