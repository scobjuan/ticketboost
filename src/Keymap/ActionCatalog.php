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

namespace GlpiPlugin\Ticketboost\Keymap;

/**
 * Static catalog of the MVP actions (PLAN.md section 3.3).
 *
 * Mirrors `frontend/src/keymap/catalog.ts`; `shared/keymap-cases.json` keeps both in sync.
 * Removing or renaming an action is a breaking change.
 */
final class ActionCatalog
{
    public const NAVIGATE = 'navigate';

    public const ASSIGN = 'assign';

    public const TICKET = 'ticket';

    public const GENERAL = 'general';

    /**
     * @var array<string, array{group: string, context: string, keys: list<string>}>
     */
    private const ACTIONS = [
        'next'            => ['group' => self::NAVIGATE, 'context' => 'ticket', 'keys' => ['KeyN', 'ArrowRight']],
        'previous'        => ['group' => self::NAVIGATE, 'context' => 'ticket', 'keys' => ['KeyP', 'ArrowLeft']],
        'list'            => ['group' => self::NAVIGATE, 'context' => 'ticket', 'keys' => ['KeyL']],
        'my_tickets'      => ['group' => self::NAVIGATE, 'context' => 'anywhere', 'keys' => ['KeyM']],
        'assign_me'       => ['group' => self::ASSIGN, 'context' => 'ticket', 'keys' => ['KeyA']],
        'assign_other'    => ['group' => self::ASSIGN, 'context' => 'ticket', 'keys' => ['KeyO']],
        'unassign_me'     => ['group' => self::ASSIGN, 'context' => 'ticket', 'keys' => ['KeyU']],
        'unassign_others' => ['group' => self::ASSIGN, 'context' => 'ticket', 'keys' => ['KeyX']],
        'change_status'   => ['group' => self::TICKET, 'context' => 'ticket', 'keys' => ['KeyS']],
        'close'           => ['group' => self::TICKET, 'context' => 'ticket', 'keys' => ['KeyC']],
        'followup'        => ['group' => self::TICKET, 'context' => 'ticket', 'keys' => ['KeyF']],
        'task'            => ['group' => self::TICKET, 'context' => 'ticket', 'keys' => ['KeyT']],
        'change_priority' => ['group' => self::TICKET, 'context' => 'ticket', 'keys' => ['KeyR']],
        'help'            => ['group' => self::GENERAL, 'context' => 'anywhere', 'keys' => ['?']],
    ];

    /**
     * @return list<string> Every action id, in panel order.
     */
    public static function ids(): array
    {
        return array_keys(self::ACTIONS);
    }

    public static function exists(string $action): bool
    {
        return isset(self::ACTIONS[$action]);
    }

    /**
     * @return list<string> The groups, in panel order.
     */
    public static function groups(): array
    {
        return [self::NAVIGATE, self::ASSIGN, self::TICKET, self::GENERAL];
    }

    public static function groupOf(string $action): ?string
    {
        return self::ACTIONS[$action]['group'] ?? null;
    }

    /**
     * @return 'ticket'|'anywhere'|null `ticket`: only on a ticket form.
     */
    public static function contextOf(string $action): ?string
    {
        $context = self::ACTIONS[$action]['context'] ?? null;

        return $context === 'ticket' || $context === 'anywhere' ? $context : null;
    }

    /**
     * @return array<string, list<string>> Action id to default bindings.
     */
    public static function defaultKeymap(): array
    {
        return array_map(
            static fn(array $action): array => $action['keys'],
            self::ACTIONS,
        );
    }
}
