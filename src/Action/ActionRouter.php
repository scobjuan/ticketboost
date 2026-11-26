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

namespace GlpiPlugin\Ticketboost\Action;

use GlpiPlugin\Ticketboost\Assignment\Assigner;
use GlpiPlugin\Ticketboost\Assignment\AssignmentRequest;
use GlpiPlugin\Ticketboost\Http\JsonResponse;
use GlpiPlugin\Ticketboost\Ticketing\TicketActionRequest;
use GlpiPlugin\Ticketboost\Ticketing\TicketActions;
use Safe\Exceptions\JsonException;

use function Safe\json_decode;

/**
 * Sends a request of `ajax/action.php` to the class that runs its action. The class
 * validates the whole body again; this only reads the action name.
 */
final class ActionRouter
{
    /**
     * How much of a request body is read. It must hold the longest solution the actions accept
     * (`TicketActionRequest::MAX_SOLUTION_LENGTH` characters, up to 6 bytes each in JSON).
     */
    public const MAX_BODY_BYTES = 65536;

    /**
     * @param string $raw The JSON request body.
     */
    public static function respond(string $raw): JsonResponse
    {
        try {
            $body = json_decode($raw, true, 8);
        } catch (JsonException) {
            return JsonResponse::error(__('Invalid request', 'ticketboost'));
        }

        $action = is_array($body) ? ($body['action'] ?? null) : null;

        return match (true) {
            is_string($action) && in_array($action, AssignmentRequest::ACTIONS, true)   => Assigner::respond($raw),
            is_string($action) && in_array($action, TicketActionRequest::ACTIONS, true) => TicketActions::respond($raw),
            default                                                                     => JsonResponse::error(__('Invalid request', 'ticketboost')),
        };
    }
}
