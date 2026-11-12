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

use GlpiPlugin\Ticketboost\Rights;

Session::checkRight('profile', UPDATE);

$profiles_id = (int) ($_POST['profiles_id'] ?? 0);
if (isset($_POST['update']) && $profiles_id > 0 && (new Profile())->getFromDB($profiles_id)) {
    ProfileRight::updateProfileRights(
        $profiles_id,
        [Rights::NAME => Rights::fromFlags(isset($_POST['use']), isset($_POST['configure']))],
    );
    Session::addMessageAfterRedirect(__('Item successfully updated'));
}

Html::back();
