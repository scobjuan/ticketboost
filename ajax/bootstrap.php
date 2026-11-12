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

use GlpiPlugin\Ticketboost\Bootstrap\Payload;
use GlpiPlugin\Ticketboost\Http\JsonResponse;
use GlpiPlugin\Ticketboost\Http\RequestGuard;

// The payload tells a user without the plugin right that the shortcuts are disabled, so
// the guard only requires a session here.
$response = RequestGuard::guard('GET', RequestGuard::LEVEL_AUTHENTICATED)
    ?? JsonResponse::success(Payload::forCurrentUser());

$response->send();
