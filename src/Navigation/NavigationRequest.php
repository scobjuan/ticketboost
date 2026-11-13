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

namespace GlpiPlugin\Ticketboost\Navigation;

use function Safe\preg_match;

/**
 * A validated request to `ajax/navigate.php`. Unknown parameters and unknown targets are
 * rejected, never ignored (RULES.md section 6).
 */
final readonly class NavigationRequest
{
    public const TARGETS = ['next', 'previous', 'list', 'my_tickets'];

    private function __construct(
        public string $to,
        public int $id,
    ) {}

    /**
     * @param array<mixed> $query The query string, normally `$_GET`.
     */
    public static function fromQuery(array $query): ?self
    {
        if (array_diff(array_keys($query), ['to', 'id']) !== []) {
            return null;
        }

        $to = $query['to'] ?? null;
        if (!is_string($to) || !in_array($to, self::TARGETS, true)) {
            return null;
        }

        $id = $query['id'] ?? null;
        if ($id === null) {
            return in_array($to, ['next', 'previous'], true) ? null : new self($to, 0);
        }

        if (!is_string($id) || preg_match('/^[1-9]\d{0,9}$/', $id) !== 1) {
            return null;
        }

        return new self($to, (int) $id);
    }
}
