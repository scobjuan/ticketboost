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

use Profile;
use Session;

/**
 * The plugin profile right `plugin_ticketboost`: READ lets a profile use the shortcuts,
 * UPDATE lets it configure the plugin.
 */
final class Rights
{
    public const NAME = 'plugin_ticketboost';

    /**
     * Lets the right reach the session of self-service profiles. GLPI drops every right
     * that is not on `Profile::$helpdesk_rights` when it loads a helpdesk profile, so
     * without this the shortcuts would stay off for self-service users.
     */
    public static function exposeToSelfService(): void
    {
        if (!in_array(self::NAME, Profile::$helpdesk_rights, true)) {
            Profile::$helpdesk_rights[] = self::NAME;
        }
    }

    public static function canUse(): bool
    {
        return Session::haveRight(self::NAME, READ);
    }

    public static function canConfigure(): bool
    {
        return Session::haveRight(self::NAME, UPDATE);
    }

    /**
     * Reads an integer from a value that GLPI's database API returns as `mixed`.
     */
    public static function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Builds the stored right value from the two profile checkboxes.
     */
    public static function fromFlags(bool $use, bool $configure): int
    {
        return ($use || $configure ? READ : 0) | ($configure ? UPDATE : 0);
    }

    /**
     * @return array{use: bool, configure: bool}
     */
    public static function toFlags(int $value): array
    {
        return [
            'use'       => ($value & READ) === READ,
            'configure' => ($value & UPDATE) === UPDATE,
        ];
    }
}
