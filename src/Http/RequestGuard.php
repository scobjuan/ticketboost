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

namespace GlpiPlugin\Ticketboost\Http;

use GlpiPlugin\Ticketboost\Rights;
use Session;

/**
 * Checks a request before an endpoint does anything: session, HTTP method, the AJAX
 * header and the plugin right.
 *
 * CSRF is enforced by the GLPI kernel before an endpoint runs (token header in GLPI 11,
 * `Sec-Fetch-Site` and `Origin` in GLPI 12), so it is not repeated here (ADR 0018).
 * Requiring `X-Requested-With` makes GLPI 11 read the token from the header instead of
 * the POST body, and keeps the endpoints out of reach of plain form posts and links.
 */
final class RequestGuard
{
    public const LEVEL_AUTHENTICATED = 'authenticated';

    public const LEVEL_USE = 'use';

    public const LEVEL_CONFIGURE = 'configure';

    /**
     * Pure decision, separated from the globals so it can be tested exhaustively.
     *
     * @param string $level One of the LEVEL_* constants; `use` and `configure` need the plugin right.
     *
     * @return JsonResponse|null The error to send, or null when the request may proceed.
     */
    public static function evaluate(
        string $expected_method,
        string $method,
        ?int $user_id,
        bool $ajax,
        string $level,
        bool $can_use,
        bool $can_configure,
    ): ?JsonResponse {
        if ($user_id === null || $user_id <= 0) {
            return JsonResponse::error(__('Authentication required', 'ticketboost'), 401);
        }

        if (strtoupper($method) !== strtoupper($expected_method)) {
            return JsonResponse::error(__('Method not allowed', 'ticketboost'), 405);
        }

        if (!$ajax) {
            return JsonResponse::error(__('Invalid request', 'ticketboost'), 400);
        }

        $allowed = match ($level) {
            self::LEVEL_AUTHENTICATED => true,
            self::LEVEL_USE           => $can_use,
            self::LEVEL_CONFIGURE     => $can_configure,
            default                   => false,
        };

        return $allowed ? null : JsonResponse::error(__('You are not allowed to do this', 'ticketboost'), 403);
    }

    /**
     * Checks the current request.
     *
     * @return JsonResponse|null The error to send, or null when the request may proceed.
     */
    public static function guard(string $expected_method = 'GET', string $level = self::LEVEL_USE): ?JsonResponse
    {
        $user_id = Session::getLoginUserID();
        $method  = is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : '';
        $ajax    = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        return self::evaluate(
            $expected_method,
            $method,
            is_int($user_id) ? $user_id : null,
            $ajax,
            $level,
            Rights::canUse(),
            Rights::canConfigure(),
        );
    }
}
