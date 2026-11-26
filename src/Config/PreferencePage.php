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

namespace GlpiPlugin\Ticketboost\Config;

use GlpiPlugin\Ticketboost\Keymap\KeymapResolver;
use GlpiPlugin\Ticketboost\Keymap\ShortcutFields;

/**
 * What the personal settings show: the overrides of a user over the administrator defaults.
 */
final class PreferencePage
{
    /**
     * @param PluginConfig                                                                          $admin      The administrator defaults.
     * @param array{enabled: bool, leader: array<string, mixed>, keymap: array<string, list<string>>} $preference The stored preference.
     * @param PreferenceForm|null                                                                   $refused    A submitted form that could not be stored.
     *
     * @return array<string, mixed> The parameters of `templates/preference.html.twig`.
     */
    public static function params(PluginConfig $admin, array $preference, ?PreferenceForm $refused = null): array
    {
        $keymap   = $refused instanceof PreferenceForm ? $refused->keymap : $preference['keymap'];
        $disabled = array_values(array_filter(
            array_map(strval(...), array_keys($keymap)),
            static fn(string $action): bool => $keymap[$action] === [],
        ));
        $stale = $refused instanceof PreferenceForm ? [] : KeymapResolver::issues($admin->keymap, $preference['keymap']);

        return [
            'enabled'      => $refused instanceof PreferenceForm ? $refused->enabled : $preference['enabled'],
            'leader'       => $refused instanceof PreferenceForm ? $refused->leader : $preference['leader'],
            'defaults'     => $admin->leader->toArray(),
            'leader_keys'  => LeaderSettings::KEYS,
            'leader_modes' => ConfigPage::leaderModes(),
            'groups'       => ConfigPage::groups(
                KeymapResolver::effective($admin->keymap, []),
                ShortcutFields::text($keymap),
                $refused instanceof PreferenceForm ? $refused->issues : $stale,
                $disabled,
            ),
            'issues'       => array_map(ConfigPage::describe(...), $refused instanceof PreferenceForm ? $refused->issues : []),
            'stale'        => array_map(ConfigPage::describe(...), $stale),
            'messages'     => ConfigPage::messages(),
        ];
    }
}
