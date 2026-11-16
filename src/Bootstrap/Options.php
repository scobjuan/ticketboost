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

use Ticket;

/**
 * The choices of the submenu actions (status and priority), translated with GLPI's own
 * texts so they read the same as in the ticket form.
 */
final class Options
{
    /**
     * @return array<string, list<array{value: string, label: string}>> Action id to its options.
     */
    public static function all(): array
    {
        $statuses = [];
        foreach ([Ticket::INCOMING, Ticket::ASSIGNED, Ticket::PLANNED, Ticket::WAITING] as $status) {
            $statuses[] = ['value' => (string) $status, 'label' => (string) Ticket::getStatus($status)];
        }

        $priorities = [];
        foreach (range(1, 6) as $priority) {
            $priorities[] = ['value' => (string) $priority, 'label' => Ticket::getPriorityName($priority)];
        }

        return ['change_status' => $statuses, 'change_priority' => $priorities];
    }
}
