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

namespace GlpiPlugin\Ticketboost\Assignment;

/**
 * A validated request to `ajax/search.php`: who can be picked for an assignment action.
 */
final readonly class SearchRequest
{
    public const ACTIONS = ['assign_other', 'unassign_others'];

    public const MAX_QUERY_LENGTH = 100;

    private function __construct(
        public string $action,
        public int $id,
        public string $query,
    ) {}

    /**
     * @param array<mixed> $query The query string, normally `$_GET`.
     */
    public static function fromQuery(array $query): ?self
    {
        if (array_diff(array_keys($query), ['action', 'id', 'q']) !== []) {
            return null;
        }

        $action = $query['action'] ?? null;
        $id     = $query['id'] ?? null;
        $text   = $query['q'] ?? '';
        if (
            !is_string($action) || !in_array($action, self::ACTIONS, true)
            || !is_string($id) || !ctype_digit($id) || $id[0] === '0' || strlen($id) > 10
            || !is_string($text) || mb_strlen($text) > self::MAX_QUERY_LENGTH
        ) {
            return null;
        }

        return new self($action, (int) $id, trim($text));
    }
}
