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

namespace GlpiPlugin\Ticketboost;

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Config\PreferenceForm;
use GlpiPlugin\Ticketboost\Config\PreferencePage;
use Html;
use Preference as UserSettings;
use Session;
use User;

/**
 * The TicketBoost tab on "My settings" and on the user form, where the personal shortcut
 * settings are changed (ADR 0021 says who may change whose).
 */
final class PreferenceTab extends CommonGLPI
{
    public static function getTypeName($nb = 0): string
    {
        return __('TicketBoost', 'ticketboost');
    }

    /**
     * Users change their own settings when their profile can use the plugin. Changing the
     * settings of somebody else needs the configure right of the plugin and the right to
     * update that user, who may not have more rights than the editor.
     */
    public static function canManage(int $users_id): bool
    {
        if ($users_id <= 0) {
            return false;
        }

        if ($users_id === Session::getLoginUserID()) {
            return Rights::canUse();
        }

        $user = new User();

        return Rights::canConfigure()
            && $user->getFromDB($users_id)
            && $user->can($users_id, UPDATE)
            && (bool) $user->currentUserHaveMoreRightThan($users_id);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        return self::canManage(self::userOf($item)) ? self::getTypeName() : '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        $users_id = self::userOf($item);
        if (!self::canManage($users_id)) {
            return false;
        }

        self::show($users_id);

        return true;
    }

    /**
     * Prints the settings form of a user. The caller checks `canManage()` first.
     *
     * @param PreferenceForm|null $refused A submitted form that could not be stored.
     */
    public static function show(int $users_id, ?PreferenceForm $refused = null): void
    {
        TemplateRenderer::getInstance()->display('@ticketboost/preference.html.twig', [
            'action'   => Html::getPrefixedUrl('/plugins/ticketboost/front/preference.form.php'),
            'users_id' => $users_id,
            'form_end' => Html::closeForm(false),
        ] + PreferencePage::params(PluginConfig::load(), Preference::forUser($users_id), $refused));
    }

    private static function userOf(CommonGLPI $item): int
    {
        if ($item instanceof UserSettings) {
            $id = Session::getLoginUserID();

            return is_int($id) ? $id : 0;
        }

        return $item instanceof User ? Rights::toInt($item->getID()) : 0;
    }
}
