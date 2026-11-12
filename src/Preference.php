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

namespace GlpiPlugin\Ticketboost;

use CommonDBTM;
use GlpiPlugin\Ticketboost\Config\LeaderSettings;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Keymap\KeymapResolver;
use stdClass;
use Throwable;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * Per-user preferences (table `glpi_plugin_ticketboost_preferences`): whether the
 * shortcuts are enabled and the user's overrides of the leader and the keymap.
 */
final class Preference extends CommonDBTM
{
    public static function getTypeName($nb = 0): string
    {
        return __('TicketBoost preferences', 'ticketboost');
    }

    /**
     * Reads the preferences of a user; defaults when the user has none.
     *
     * @return array{enabled: bool, leader: array<string, mixed>, keymap: array<string, list<string>>}
     */
    public static function forUser(int $users_id): array
    {
        $preference = new self();
        if ($users_id <= 0 || !$preference->getFromDBByCrit(['users_id' => $users_id])) {
            return ['enabled' => true, 'leader' => [], 'keymap' => []];
        }

        $keymap = [];
        foreach (self::decode($preference->fields['keymap'] ?? null) as $action => $bindings) {
            if (is_string($action) && is_array($bindings)) {
                $keymap[$action] = array_values(array_filter($bindings, is_string(...)));
            }
        }

        return [
            'enabled' => Rights::toInt($preference->fields['enabled'] ?? 1) === 1,
            'leader'  => array_filter(self::decode($preference->fields['leader'] ?? null), is_string(...), ARRAY_FILTER_USE_KEY),
            'keymap'  => $keymap,
        ];
    }

    /**
     * Stores the preferences of a user after validating them against the admin keymap.
     *
     * @param array<array-key, mixed> $leader Leader overrides; unknown fields are dropped.
     * @param array<array-key, mixed> $keymap Keymap overrides.
     *
     * @return bool False when the keymap overrides are invalid or the storage failed.
     */
    public static function saveForUser(int $users_id, bool $enabled, array $leader, array $keymap): bool
    {
        if ($users_id <= 0) {
            return false;
        }

        $normalized = KeymapResolver::normalizeLayer($keymap);
        if ($normalized === null || KeymapResolver::issues(PluginConfig::load()->keymap, $normalized) !== []) {
            return false;
        }

        $known  = array_intersect_key($leader, (new LeaderSettings())->toArray());
        $values = [
            'users_id' => $users_id,
            'enabled'  => $enabled ? 1 : 0,
            'leader'   => json_encode($known === [] ? new stdClass() : $known),
            'keymap'   => json_encode($normalized === [] ? new stdClass() : $normalized),
        ];

        $preference = new self();
        if ($preference->getFromDBByCrit(['users_id' => $users_id])) {
            return $preference->update(['id' => $preference->getID()] + $values);
        }

        return $preference->add($values) !== false;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function decode(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true);
        } catch (Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
