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

/**
 * Circular walk over a list of ids, the rule behind the next and previous actions.
 */
final class ListWalker
{
    /**
     * Finds the id that follows (`$step` 1) or precedes (`$step` -1) the current one,
     * wrapping around at both ends and skipping the ids that are not acceptable.
     *
     * When the current id is not in the list, `next` yields the first acceptable id and
     * `previous` the last one.
     *
     * @param list<int>        $ids     The ids in display order.
     * @param int              $step    1 for next, -1 for previous.
     * @param callable(int): bool $accept Whether the user may go to that id.
     *
     * @return int|null The id to go to, or null when there is no other acceptable id.
     */
    public static function step(array $ids, int $current, int $step, callable $accept): ?int
    {
        $count = count($ids);
        if ($count === 0 || ($step !== 1 && $step !== -1)) {
            return null;
        }

        $position = array_search($current, $ids, true);
        $start    = $position === false ? ($step > 0 ? -1 : $count) : $position;

        for ($offset = 1; $offset <= $count; $offset++) {
            $candidate = $ids[(($start + $step * $offset) % $count + $count) % $count];
            if ($candidate !== $current && $accept($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
