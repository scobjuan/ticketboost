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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Config\PreferenceForm;
use GlpiPlugin\Ticketboost\Preference;
use GlpiPlugin\Ticketboost\PreferenceTab;
use GlpiPlugin\Ticketboost\Rights;

// GLPI refuses a POST that fails its CSRF check before this file runs (ADR 0018).
Session::checkLoginUser();

$users_id = Rights::toInt($_POST['users_id'] ?? $_GET['users_id'] ?? Session::getLoginUserID());
if (!PreferenceTab::canManage($users_id)) {
    throw new AccessDeniedHttpException();
}

$refused = null;
if (isset($_POST['restore']) || isset($_POST['update'])) {
    $form = isset($_POST['restore'])
        ? new PreferenceForm(true, [], [], [])
        : PreferenceForm::fromPost($_POST, PluginConfig::load());

    if ($form->isValid()) {
        if (Preference::saveForUser($users_id, $form->enabled, $form->leader, $form->keymap)) {
            Session::addMessageAfterRedirect(__('Item successfully updated'));
        } else {
            Session::addMessageAfterRedirect(__('The settings were not saved', 'ticketboost'), false, ERROR);
        }

        Html::back();
    }

    $refused = $form;
}

$title = __('TicketBoost', 'ticketboost');
if (Session::getCurrentInterface() === 'helpdesk') {
    Html::helpHeader($title);
    PreferenceTab::show($users_id, $refused);
    Html::helpFooter();
} else {
    Html::header($title, $_SERVER['PHP_SELF'], 'preference');
    PreferenceTab::show($users_id, $refused);
    Html::footer();
}
