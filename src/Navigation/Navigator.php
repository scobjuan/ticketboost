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

namespace GlpiPlugin\Ticketboost\Navigation;

use CommonITILActor;
use Glpi\DBAL\QuerySubQuery;
use GlpiPlugin\Ticketboost\Http\JsonResponse;
use GlpiPlugin\Ticketboost\Rights;
use Session;
use Ticket;

/**
 * Works out where the navigation actions go: next and previous ticket, the ticket list and
 * the tickets of the current user. It only computes a URL; the browser does the move, and
 * GLPI checks the right on the ticket again when the page loads.
 */
final class Navigator
{
    /**
     * How many unresolved tickets are walked when the user has no result list in the session.
     */
    public const FALLBACK_LIMIT = 200;

    /**
     * Search option of the "Requester" and "Technician" columns of the ticket list.
     */
    private const FIELD_REQUESTER  = 4;

    private const FIELD_TECHNICIAN = 5;

    /**
     * @param array<mixed> $query The query string, normally `$_GET`.
     */
    public static function respond(array $query): JsonResponse
    {
        $request = NavigationRequest::fromQuery($query);
        if (!$request instanceof NavigationRequest) {
            return JsonResponse::error(__('Invalid request', 'ticketboost'));
        }

        if (!Ticket::canView()) {
            return JsonResponse::error(__('You are not allowed to do this', 'ticketboost'), 403);
        }

        return match ($request->to) {
            'list'       => JsonResponse::success(null, '', Ticket::getSearchURL(false)),
            'my_tickets' => JsonResponse::success(null, '', self::myTicketsUrl()),
            default      => self::step($request),
        };
    }

    /**
     * The ticket list filtered to the tickets the user works on: assigned to them for
     * technicians, requested by them for self-service.
     */
    public static function myTicketsUrl(): string
    {
        $field = Session::getCurrentInterface() === 'central' ? self::FIELD_TECHNICIAN : self::FIELD_REQUESTER;

        return Ticket::getSearchURL(false) . '?' . http_build_query([
            'reset'    => 'reset',
            'criteria' => [[
                'link'       => 'AND',
                'field'      => $field,
                'searchtype' => 'equals',
                'value'      => Session::getLoginUserID(),
            ]],
        ]);
    }

    private static function step(NavigationRequest $request): JsonResponse
    {
        $ids      = self::sessionList();
        $fallback = !in_array($request->id, $ids, true);
        if ($fallback) {
            $ids = self::unresolvedTickets();
        }

        $target = ListWalker::step(
            $ids,
            $request->id,
            $request->to === 'next' ? 1 : -1,
            self::viewable(...),
        );
        if ($target === null) {
            return JsonResponse::error(__('There is no other ticket to go to', 'ticketboost'), 404);
        }

        if ($fallback) {
            // Shown by GLPI on the page that opens next, as the decision 0004 asks for.
            Session::addMessageAfterRedirect(
                __('There was no result list, so you are moving through your unresolved tickets', 'ticketboost'),
                false,
                INFO,
            );
        }

        return JsonResponse::success(['id' => $target], '', Ticket::getFormURLWithID($target, false));
    }

    /**
     * The ids GLPI keeps from the last search, which native next and previous use too.
     *
     * @return list<int>
     */
    private static function sessionList(): array
    {
        $lists = $_SESSION['glpilistitems'] ?? [];
        $list  = is_array($lists) ? ($lists[Ticket::class] ?? []) : [];
        if (!is_array($list)) {
            return [];
        }

        return array_values(array_filter(
            array_map(Rights::toInt(...), $list),
            static fn(int $id): bool => $id > 0,
        ));
    }

    /**
     * The unresolved tickets of the user, most urgent first, for users who arrive on a
     * ticket without a search result list (decision 0004): assigned to them for
     * technicians, requested by them for self-service.
     *
     * @return list<int>
     */
    private static function unresolvedTickets(): array
    {
        global $DB;

        $table = Ticket::getTable();
        $role  = Session::getCurrentInterface() === 'central' ? CommonITILActor::ASSIGN : CommonITILActor::REQUESTER;

        $ids = [];
        foreach ($DB->request([
            'SELECT' => 'id',
            'FROM'   => $table,
            'WHERE'  => [
                'is_deleted' => 0,
                'status'     => Ticket::getNotSolvedStatusArray(),
                'id'         => new QuerySubQuery([
                    'SELECT' => 'tickets_id',
                    'FROM'   => 'glpi_tickets_users',
                    'WHERE'  => ['users_id' => Session::getLoginUserID(), 'type' => $role],
                ]),
            ] + getEntitiesRestrictCriteria($table),
            'ORDER'  => ['priority DESC', 'date ASC', 'id ASC'],
            'LIMIT'  => self::FALLBACK_LIMIT,
        ]) as $row) {
            $ids[] = Rights::toInt(is_array($row) ? ($row['id'] ?? 0) : 0);
        }

        return $ids;
    }

    private static function viewable(int $id): bool
    {
        $ticket = new Ticket();

        return $ticket->getFromDB($id) && $ticket->canViewItem();
    }
}
