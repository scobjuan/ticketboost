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
use GlpiPlugin\Ticketboost\Http\JsonResponse;
use GlpiPlugin\Ticketboost\Rights;
use Group_Ticket;
use Session;
use Ticket;
use Ticket_User;

/**
 * Runs the assignment actions through the native GLPI relation classes, so business rules,
 * status changes, history and notifications behave as in the ticket form. Rights are checked
 * on the specific ticket for every request.
 */
final class Assigner
{
    /**
     * @param string $raw The JSON request body.
     */
    public static function respond(string $raw): JsonResponse
    {
        $request = AssignmentRequest::fromJson($raw);
        if (!$request instanceof AssignmentRequest) {
            return JsonResponse::error(__('Invalid request', 'ticketboost'));
        }

        $ticket = new Ticket();
        // The same answer for a missing ticket and one the user cannot see.
        if (!$ticket->getFromDB($request->id) || !$ticket->canViewItem()) {
            return JsonResponse::error(__('The ticket does not exist or you cannot see it', 'ticketboost'), 404);
        }

        if (Rights::toInt($ticket->fields['is_deleted'] ?? 0) === 1 || Rights::toInt($ticket->fields['status'] ?? 0) === Ticket::CLOSED) {
            return JsonResponse::error(__('The ticket is closed or deleted', 'ticketboost'), 409);
        }

        $response = match ($request->action) {
            'assign_me'       => self::assignMe($ticket),
            'assign_other'    => self::assignOther($ticket, $request->actors[0]),
            'unassign_me'     => self::unassignMe($ticket),
            default           => self::unassignOthers($ticket, $request->actors),
        };

        if ($response->ok) {
            // Shown by GLPI on the page that opens next, which reloads the ticket.
            Session::addMessageAfterRedirect($response->message, false, INFO);
        }

        return $response;
    }

    private static function assignMe(Ticket $ticket): JsonResponse
    {
        if (!$ticket->canUpdateItem() || !$ticket->canAssign() && !$ticket->canAssignToMe()) {
            return self::forbidden();
        }

        $me = Actor::user((int) Session::getLoginUserID());
        if (Assignees::link($ticket->getID(), $me) instanceof CommonDBTM) {
            return JsonResponse::error(__('You are already assigned to this ticket', 'ticketboost'), 409);
        }

        return self::add($ticket, $me, __('The ticket is now assigned to you', 'ticketboost'));
    }

    private static function assignOther(Ticket $ticket, Actor $actor): JsonResponse
    {
        if (!$ticket->canAssign()) {
            return self::forbidden();
        }

        if (!Assignees::assignable($ticket, $actor)) {
            return JsonResponse::error(__('That user or group cannot be assigned to this ticket', 'ticketboost'));
        }

        if (Assignees::link($ticket->getID(), $actor) instanceof CommonDBTM) {
            return JsonResponse::error(__('It is already assigned to this ticket', 'ticketboost'), 409);
        }

        return self::add($ticket, $actor, __('The ticket was assigned', 'ticketboost'));
    }

    private static function unassignMe(Ticket $ticket): JsonResponse
    {
        if (!$ticket->canUpdateItem()) {
            return self::forbidden();
        }

        $link = Assignees::link($ticket->getID(), Actor::user((int) Session::getLoginUserID()));
        if (!$link instanceof CommonDBTM) {
            return JsonResponse::error(__('You are not assigned to this ticket', 'ticketboost'), 409);
        }

        if (!$link->delete(['id' => $link->getID()])) {
            return self::failed();
        }

        return self::done($ticket, __('You are no longer assigned to the ticket', 'ticketboost'));
    }

    /**
     * @param list<Actor> $actors
     */
    private static function unassignOthers(Ticket $ticket, array $actors): JsonResponse
    {
        if (!$ticket->canAssign()) {
            return self::forbidden();
        }

        $me    = (int) Session::getLoginUserID();
        $links = [];
        foreach ($actors as $actor) {
            // Removing yourself is its own action.
            $link = $actor->kind === Actor::USER && $actor->id === $me
                ? null
                : Assignees::link($ticket->getID(), $actor);
            if (!$link instanceof CommonDBTM) {
                return JsonResponse::error(__('One of the selected assignees is not assigned to this ticket', 'ticketboost'), 409);
            }

            $links[] = $link;
        }

        $failed = false;
        foreach ($links as $link) {
            $failed = !$link->delete(['id' => $link->getID()]) || $failed;
        }

        return $failed ? self::failed() : self::done($ticket, __('The selected assignees were removed', 'ticketboost'));
    }

    private static function add(Ticket $ticket, Actor $actor, string $message): JsonResponse
    {
        $link  = $actor->kind === Actor::USER ? new Ticket_User() : new Group_Ticket();
        $input = ['tickets_id' => $ticket->getID(), 'type' => CommonITILActor::ASSIGN];
        $input += $actor->kind === Actor::USER
            ? ['users_id' => $actor->id, 'use_notification' => 1]
            : ['groups_id' => $actor->id];

        if ($link->add($input) === false) {
            return self::failed();
        }

        return self::done($ticket, $message);
    }

    private static function done(Ticket $ticket, string $message): JsonResponse
    {
        return JsonResponse::success(['id' => $ticket->getID()], $message, Ticket::getFormURLWithID($ticket->getID(), false));
    }

    private static function forbidden(): JsonResponse
    {
        return JsonResponse::error(__('You are not allowed to do this', 'ticketboost'), 403);
    }

    private static function failed(): JsonResponse
    {
        return JsonResponse::error(__('The change could not be saved', 'ticketboost'), 500);
    }
}
