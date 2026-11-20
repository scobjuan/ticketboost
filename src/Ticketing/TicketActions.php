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

namespace GlpiPlugin\Ticketboost\Ticketing;

use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Http\JsonResponse;
use GlpiPlugin\Ticketboost\Rights;
use ITILSolution;
use Session;
use Ticket;

/**
 * Runs the ticket actions (change status, change priority, close) through the native
 * `Ticket` and `ITILSolution` classes, so business rules, history and notifications behave
 * as in the ticket form. Rights are checked on the specific ticket for every request.
 */
final class TicketActions
{
    /**
     * @param string $raw The JSON request body.
     */
    public static function respond(string $raw): JsonResponse
    {
        $request = TicketActionRequest::fromJson($raw);
        if (!$request instanceof TicketActionRequest) {
            return JsonResponse::error(__('Invalid request', 'ticketboost'));
        }

        // The panel only offers these actions in the central interface; the server agrees.
        if (Session::getCurrentInterface() !== 'central') {
            return self::forbidden();
        }

        $ticket = new Ticket();
        // The same answer for a missing ticket and one the user cannot see.
        if (!$ticket->getFromDB($request->id) || !$ticket->canViewItem()) {
            return JsonResponse::error(__('The ticket does not exist or you cannot see it', 'ticketboost'), 404);
        }

        if (Rights::toInt($ticket->fields['is_deleted'] ?? 0) === 1 || self::status($ticket) === Ticket::CLOSED) {
            return JsonResponse::error(__('The ticket is closed or deleted', 'ticketboost'), 409);
        }

        $response = match ($request->action) {
            'change_status'   => self::changeStatus($ticket, $request->number),
            'change_priority' => self::changePriority($ticket, $request->number),
            default           => self::close($ticket, $request->text),
        };

        if ($response->ok) {
            // Shown by GLPI on the page that opens next, which reloads the ticket.
            Session::addMessageAfterRedirect($response->message, false, INFO);
        }

        return $response;
    }

    private static function changeStatus(Ticket $ticket, int $status): JsonResponse
    {
        if (!$ticket->canUpdateItem()) {
            return self::forbidden();
        }

        $current = self::status($ticket);
        if ($status === $current) {
            return JsonResponse::error(__('The ticket already has that status', 'ticketboost'), 409);
        }

        if (!array_key_exists($status, Ticket::getAllowedStatusArray($current))) {
            return JsonResponse::error(__('That status is not allowed for this ticket', 'ticketboost'));
        }

        return self::update($ticket, 'status', $status, __('The ticket status was changed', 'ticketboost'));
    }

    private static function changePriority(Ticket $ticket, int $priority): JsonResponse
    {
        if (!$ticket->canUpdateItem() || !Rights::has(Ticket::$rightname, Ticket::CHANGEPRIORITY)) {
            return self::forbidden();
        }

        if ($priority === Rights::toInt($ticket->fields['priority'] ?? 0)) {
            return JsonResponse::error(__('The ticket already has that priority', 'ticketboost'), 409);
        }

        return self::update($ticket, 'priority', $priority, __('The ticket priority was changed', 'ticketboost'));
    }

    private static function close(Ticket $ticket, string $text): JsonResponse
    {
        if (PluginConfig::load()->closeMode === PluginConfig::CLOSE_DIRECT) {
            return self::closeDirectly($ticket, $text);
        }

        if (!$ticket->canSolve()) {
            return self::forbidden();
        }

        if ($text === '') {
            return JsonResponse::error(__('Write the solution first', 'ticketboost'));
        }

        $solution = new ITILSolution();
        $added    = $solution->add([
            'itemtype'         => Ticket::class,
            'items_id'         => $ticket->getID(),
            'solutiontypes_id' => 0,
            // Plain text typed in the panel, stored as the HTML GLPI expects.
            'content'          => '<p>' . nl2br(htmlspecialchars($text, ENT_QUOTES)) . '</p>',
        ]);

        return $added === false
            ? JsonResponse::error(__('GLPI did not accept the change', 'ticketboost'), 422)
            : self::done($ticket, __('The solution was added to the ticket', 'ticketboost'));
    }

    private static function closeDirectly(Ticket $ticket, string $text): JsonResponse
    {
        if ($text !== '') {
            return JsonResponse::error(__('Invalid request', 'ticketboost'));
        }

        if (!$ticket->canUpdateItem() || !Ticket::isAllowedStatus(self::status($ticket), Ticket::CLOSED)) {
            return self::forbidden();
        }

        return self::update($ticket, 'status', Ticket::CLOSED, __('The ticket was closed', 'ticketboost'));
    }

    /**
     * Changes one field through `Ticket::update()` and checks that GLPI, which may apply
     * business rules, kept the value.
     */
    private static function update(Ticket $ticket, string $field, int $value, string $message): JsonResponse
    {
        if (!$ticket->update(['id' => $ticket->getID(), $field => $value])) {
            return JsonResponse::error(__('The change could not be saved', 'ticketboost'), 500);
        }

        $fresh = new Ticket();
        if (!$fresh->getFromDB($ticket->getID()) || Rights::toInt($fresh->fields[$field] ?? 0) !== $value) {
            return JsonResponse::error(__('GLPI did not accept the change', 'ticketboost'), 422);
        }

        return self::done($ticket, $message);
    }

    private static function status(Ticket $ticket): int
    {
        return Rights::toInt($ticket->fields['status'] ?? 0);
    }

    private static function done(Ticket $ticket, string $message): JsonResponse
    {
        return JsonResponse::success(['id' => $ticket->getID()], $message, Ticket::getFormURLWithID($ticket->getID(), false));
    }

    private static function forbidden(): JsonResponse
    {
        return JsonResponse::error(__('You are not allowed to do this', 'ticketboost'), 403);
    }
}
