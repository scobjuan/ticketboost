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

namespace GlpiPlugin\Ticketboost\Assignment;

use CommonDBTM;
use CommonITILActor;
use GlpiPlugin\Ticketboost\Rights;
use Group;
use Group_Ticket;
use Profile;
use Ticket;
use Ticket_User;
use User;

use function getAncestorsOf;
use function getUserName;

/**
 * Reads who a ticket is assigned to and who may be assigned to it, always through the
 * native GLPI relation classes.
 */
final class Assignees
{
    /**
     * The assignment link between a ticket and someone, or null when there is none.
     */
    public static function link(int $ticket_id, Actor $actor): ?CommonDBTM
    {
        $user = $actor->kind === Actor::USER;
        $link = $user ? new Ticket_User() : new Group_Ticket();

        $found = $link->getFromDBByCrit([
            'tickets_id'                 => $ticket_id,
            $user ? 'users_id' : 'groups_id' => $actor->id,
            'type'                       => CommonITILActor::ASSIGN,
        ]);

        return $found ? $link : null;
    }

    /**
     * Everyone the ticket is assigned to, users first.
     *
     * @return list<Actor>
     */
    public static function current(int $ticket_id): array
    {
        global $DB;

        $actors = [];
        foreach ([[Ticket_User::getTable(), 'users_id', Actor::USER], [Group_Ticket::getTable(), 'groups_id', Actor::GROUP]] as [$table, $field, $kind]) {
            foreach ($DB->request([
                'SELECT' => $field,
                'FROM'   => $table,
                'WHERE'  => ['tickets_id' => $ticket_id, 'type' => CommonITILActor::ASSIGN],
            ]) as $row) {
                $id = Rights::toInt(is_array($row) ? ($row[$field] ?? 0) : 0);
                // A zero user is an assignee known only by e-mail address: nothing to list or remove.
                if ($id > 0) {
                    $actors[] = $kind === Actor::USER ? Actor::user($id) : Actor::group($id);
                }
            }
        }

        return $actors;
    }

    /**
     * A name to show for the actor.
     */
    public static function label(Actor $actor): string
    {
        if ($actor->kind === Actor::USER) {
            return getUserName($actor->id);
        }

        $group = new Group();

        return $group->getFromDB($actor->id) && is_string($group->fields['name'] ?? null)
            ? $group->fields['name']
            : '#' . $actor->id;
    }

    /**
     * Whether the actor may be assigned to the ticket: an active user with the right to own
     * tickets in its entity, or a group that can be assigned and is visible there.
     */
    public static function assignable(Ticket $ticket, Actor $actor): bool
    {
        $entity = Rights::toInt($ticket->fields['entities_id'] ?? 0);

        if ($actor->kind === Actor::USER) {
            $user = new User();

            return $user->getFromDB($actor->id)
                && Rights::toInt($user->fields['is_active'] ?? 0) === 1
                && Rights::toInt($user->fields['is_deleted'] ?? 1) === 0
                && Profile::haveUserRight($actor->id, Ticket::$rightname, Ticket::OWN, $entity);
        }

        $group = new Group();
        if (!$group->getFromDB($actor->id) || Rights::toInt($group->fields['is_assign'] ?? 0) !== 1) {
            return false;
        }

        $group_entity = Rights::toInt($group->fields['entities_id'] ?? -1);

        return $group_entity === $entity
            || (Rights::toInt($group->fields['is_recursive'] ?? 0) === 1
                && array_key_exists($group_entity, getAncestorsOf('glpi_entities', $entity)));
    }
}
