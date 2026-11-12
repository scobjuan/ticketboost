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

use ITILFollowup;
use ITILSolution;
use Session;
use Ticket;
use TicketTask;

/**
 * Which actions the current user may use, from their native GLPI rights.
 *
 * This only decides what the panel shows. Every action endpoint checks the native right
 * on the specific item again (PLAN.md section 3.4).
 */
final class ActionAvailability
{
    /**
     * Actions that have a working endpoint. It grows with each phase; the panel never
     * lists an action that would do nothing.
     */
    public const IMPLEMENTED = ['help'];

    /**
     * @param array<string, bool|int> $caps Rights of the user, see {@see self::capabilities()}.
     *
     * @return list<string> The allowed action ids, in panel order.
     */
    public static function fromCapabilities(array $caps): array
    {
        // GLPI 11 returns the masked right (an int) from Session::haveRight(), GLPI 12 a bool.
        $can = static fn(string $name): bool => (bool) ($caps[$name] ?? false);

        $allowed = [
            'next'            => $can('view'),
            'previous'        => $can('view'),
            'list'            => $can('view'),
            'my_tickets'      => $can('view'),
            'assign_me'       => $can('central') && ($can('assign') || $can('own')),
            'assign_other'    => $can('central') && $can('assign'),
            'unassign_me'     => $can('central') && $can('update'),
            'unassign_others' => $can('central') && $can('assign'),
            'change_status'   => $can('central') && $can('update'),
            'close'           => $can('central') && ($can('update') || $can('solution')),
            'followup'        => $can('followup'),
            'task'            => $can('central') && $can('task'),
            'change_priority' => $can('central') && $can('priority'),
            'help'            => true,
        ];

        return array_keys(array_filter($allowed));
    }

    /**
     * Values are truthy or falsy rather than strictly bool: see {@see self::fromCapabilities()}.
     *
     * @return array<string, bool|int>
     */
    public static function capabilities(): array
    {
        return [
            'view'     => Ticket::canView(),
            'central'  => Session::getCurrentInterface() === 'central',
            'assign'   => Session::haveRight(Ticket::$rightname, Ticket::ASSIGN),
            'own'      => Session::haveRight(Ticket::$rightname, Ticket::OWN),
            'update'   => Ticket::canUpdate(),
            'solution' => ITILSolution::canCreate(),
            'priority' => Session::haveRight(Ticket::$rightname, Ticket::CHANGEPRIORITY),
            'followup' => ITILFollowup::canCreate(),
            'task'     => TicketTask::canCreate(),
        ];
    }

    /**
     * @return list<string> The implemented actions the current user may use.
     */
    public static function forCurrentUser(): array
    {
        $allowed = self::fromCapabilities(self::capabilities());

        return array_values(array_filter(
            $allowed,
            static fn(string $action): bool => in_array($action, self::IMPLEMENTED, true),
        ));
    }
}
