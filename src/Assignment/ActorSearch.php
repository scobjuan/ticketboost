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

use Glpi\DBAL\QuerySubQuery;
use GlpiPlugin\Ticketboost\Http\JsonResponse;
use GlpiPlugin\Ticketboost\Rights;
use Group;
use Session;
use Ticket;
use User;

use function getEntitiesRestrictCriteria;

/**
 * Finds who can be picked in the assignment pickers: the users and groups a ticket can be
 * assigned to, or the people it is assigned to now.
 */
final class ActorSearch
{
    /**
     * Most results of each kind.
     */
    public const LIMIT = 10;

    /**
     * Most candidate users read while looking for ones that really have the right in the
     * entity of the ticket (the database narrows by right, the entity is checked per user).
     */
    public const SCAN_LIMIT = 100;

    /**
     * @param array<mixed> $query The query string, normally `$_GET`.
     */
    public static function respond(array $query): JsonResponse
    {
        $request = SearchRequest::fromQuery($query);
        if (!$request instanceof SearchRequest) {
            return JsonResponse::error(__('Invalid request', 'ticketboost'));
        }

        $ticket = new Ticket();
        if (!$ticket->getFromDB($request->id) || !$ticket->canViewItem()) {
            return JsonResponse::error(__('The ticket does not exist or you cannot see it', 'ticketboost'), 404);
        }

        if (!$ticket->canAssign()) {
            return JsonResponse::error(__('You are not allowed to do this', 'ticketboost'), 403);
        }

        $current = Assignees::current($ticket->getID());
        $items   = $request->action === 'assign_other'
            ? self::candidates($ticket, $request->query, $current)
            : self::assigned($request->query, $current);

        return JsonResponse::success($items);
    }

    /**
     * The people the ticket is assigned to now, except the current user.
     *
     * @param list<Actor> $current
     *
     * @return list<array{id: string, label: string, detail: string}>
     */
    private static function assigned(string $text, array $current): array
    {
        $me    = (int) Session::getLoginUserID();
        $items = [];
        foreach ($current as $actor) {
            $label = Assignees::label($actor);
            if (($actor->kind === Actor::USER && $actor->id === $me) || !self::matches($label, $text)) {
                continue;
            }

            $items[] = self::item($actor, $label);
        }

        return $items;
    }

    /**
     * @param list<Actor> $current
     *
     * @return list<array{id: string, label: string, detail: string}>
     */
    private static function candidates(Ticket $ticket, string $text, array $current): array
    {
        $taken = array_map(static fn(Actor $actor): string => $actor->toString(), $current);
        $items = [];
        foreach (self::users($ticket, $text) as $actor) {
            if (!in_array($actor->toString(), $taken, true)) {
                $items[] = self::item($actor, Assignees::label($actor));
            }
        }

        foreach (self::groups($ticket, $text) as $actor) {
            if (!in_array($actor->toString(), $taken, true)) {
                $items[] = self::item($actor, Assignees::label($actor));
            }
        }

        return $items;
    }

    /**
     * @return list<Actor>
     */
    private static function users(Ticket $ticket, string $text): array
    {
        global $DB;

        $where = [
            'is_active'  => 1,
            'is_deleted' => 0,
            'id'         => new QuerySubQuery([
                'SELECT'     => 'glpi_profiles_users.users_id',
                'FROM'       => 'glpi_profiles_users',
                'INNER JOIN' => [
                    'glpi_profilerights' => [
                        'FKEY' => ['glpi_profilerights' => 'profiles_id', 'glpi_profiles_users' => 'profiles_id'],
                    ],
                ],
                'WHERE'      => [
                    'glpi_profilerights.name'   => Ticket::$rightname,
                    'glpi_profilerights.rights' => ['&', Ticket::OWN],
                ],
            ]),
        ];
        if ($text !== '') {
            $like = ['LIKE', '%' . addcslashes($text, '%_\\') . '%'];
            $where[] = ['OR' => ['name' => $like, 'realname' => $like, 'firstname' => $like]];
        }

        $actors = [];
        foreach ($DB->request([
            'SELECT' => 'id',
            'FROM'   => User::getTable(),
            'WHERE'  => $where,
            'ORDER'  => ['realname', 'firstname', 'name'],
            'LIMIT'  => self::SCAN_LIMIT,
        ]) as $row) {
            $actor = Actor::user(Rights::toInt(is_array($row) ? ($row['id'] ?? 0) : 0));
            if (Assignees::assignable($ticket, $actor)) {
                $actors[] = $actor;
            }

            if (count($actors) >= self::LIMIT) {
                break;
            }
        }

        return $actors;
    }

    /**
     * @return list<Actor>
     */
    private static function groups(Ticket $ticket, string $text): array
    {
        global $DB;

        $where = [
            'is_assign' => 1,
            getEntitiesRestrictCriteria(Group::getTable(), 'entities_id', Rights::toInt($ticket->fields['entities_id'] ?? 0), true),
        ];
        if ($text !== '') {
            $where[] = ['completename' => ['LIKE', '%' . addcslashes($text, '%_\\') . '%']];
        }

        $actors = [];
        foreach ($DB->request([
            'SELECT' => 'id',
            'FROM'   => Group::getTable(),
            'WHERE'  => $where,
            'ORDER'  => 'completename',
            'LIMIT'  => self::LIMIT,
        ]) as $row) {
            $actors[] = Actor::group(Rights::toInt(is_array($row) ? ($row['id'] ?? 0) : 0));
        }

        return $actors;
    }

    /**
     * @return array{id: string, label: string, detail: string}
     */
    private static function item(Actor $actor, string $label): array
    {
        return [
            'id'     => $actor->toString(),
            'label'  => $label,
            'detail' => $actor->kind === Actor::USER ? User::getTypeName(1) : Group::getTypeName(1),
        ];
    }

    private static function matches(string $label, string $text): bool
    {
        return $text === '' || mb_stripos($label, $text) !== false;
    }
}
