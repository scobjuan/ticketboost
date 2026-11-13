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

namespace GlpiPlugin\Ticketboost\Bootstrap;

use GlpiPlugin\Ticketboost\Config\LeaderSettings;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Keymap\KeymapResolver;
use GlpiPlugin\Ticketboost\Preference;
use GlpiPlugin\Ticketboost\Rights;
use Session;
use stdClass;

/**
 * The data the frontend needs to start (`AppConfig` in `frontend/src/app.ts`): the
 * effective leader and keymap, the actions the user may use and the translated labels.
 */
final class Payload
{
    /**
     * Pure assembly, separated from GLPI so it can be tested without a session.
     *
     * @param array{enabled: bool, leader: array<string, mixed>, keymap: array<string, list<string>>} $preference
     * @param list<string>                                                                          $available
     * @param array<string, string>                                                                 $labels
     *
     * @return array<string, mixed>
     */
    public static function build(
        bool $can_use,
        PluginConfig $config,
        array $preference,
        array $available,
        array $labels,
    ): array {
        if (!$can_use || !$preference['enabled']) {
            return ['enabled' => false];
        }

        $leader = LeaderSettings::fromArray(array_merge($config->leader->toArray(), $preference['leader']));

        return [
            'enabled'   => true,
            'leader'    => $leader->toArray(),
            'keymap'    => KeymapResolver::effective($config->keymap, $preference['keymap']),
            'available' => $available,
            'labels'    => $labels,
            'options'   => new stdClass(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forCurrentUser(): array
    {
        $user_id = Session::getLoginUserID();

        return self::build(
            Rights::canUse(),
            PluginConfig::load(),
            Preference::forUser(is_int($user_id) ? $user_id : 0),
            ActionAvailability::forCurrentUser(),
            Labels::all(),
        );
    }
}
