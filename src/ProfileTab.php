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
use Html;
use Profile;
use ProfileRight;
use Session;

/**
 * The TicketBoost tab on the profile form, where an administrator decides which profiles
 * can use and configure the plugin.
 */
final class ProfileTab extends CommonGLPI
{
    public static function getTypeName($nb = 0): string
    {
        return __('TicketBoost', 'ticketboost');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        return $item instanceof Profile && Profile::canView() ? self::getTypeName() : '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if (!$item instanceof Profile) {
            return false;
        }

        $profiles_id = (int) $item->getID();
        $rights      = ProfileRight::getProfileRights($profiles_id, [Rights::NAME]);
        $flags       = Rights::toFlags(Rights::toInt($rights[Rights::NAME] ?? 0));
        $can_edit    = (bool) Session::haveRight(Profile::$rightname, UPDATE);

        echo self::render($profiles_id, $flags, $can_edit);

        return true;
    }

    /**
     * @param array{use: bool, configure: bool} $flags
     */
    public static function render(int $profiles_id, array $flags, bool $can_edit): string
    {
        $url      = htmlspecialchars(Html::getPrefixedUrl('/plugins/ticketboost/front/profile.form.php'));
        $disabled = $can_edit ? '' : ' disabled';
        $checkbox = static fn(string $name, string $label, bool $checked): string => sprintf(
            '<label class="form-check"><input class="form-check-input" type="checkbox" name="%s" value="1"%s%s>'
            . '<span class="form-check-label">%s</span></label>',
            $name,
            $checked ? ' checked' : '',
            $disabled,
            htmlspecialchars($label),
        );

        $out  = '<div class="m-3"><form method="post" action="' . $url . '">';
        $out .= '<input type="hidden" name="profiles_id" value="' . $profiles_id . '">';
        $out .= '<h3>' . htmlspecialchars(self::getTypeName()) . '</h3>';
        $out .= $checkbox('use', __('Use the keyboard shortcuts', 'ticketboost'), $flags['use']);
        $out .= $checkbox('configure', __('Configure TicketBoost', 'ticketboost'), $flags['configure']);
        if ($can_edit) {
            $out .= '<button class="btn btn-primary mt-3" type="submit" name="update" value="1">'
                . htmlspecialchars(_x('button', 'Save')) . '</button>';
        }

        $out .= Html::closeForm(false);

        return $out . '</div>';
    }
}
