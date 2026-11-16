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

use function Safe\preg_match;

/**
 * Someone a ticket can be assigned to: a user or a group. The browser names it as
 * `user:12` or `group:3`; anything else is rejected.
 */
final readonly class Actor
{
    public const USER = 'user';

    public const GROUP = 'group';

    private function __construct(
        public string $kind,
        public int $id,
    ) {}

    public static function user(int $id): self
    {
        return new self(self::USER, $id);
    }

    public static function group(int $id): self
    {
        return new self(self::GROUP, $id);
    }

    public static function fromString(mixed $value): ?self
    {
        if (!is_string($value) || preg_match('/^(user|group):([1-9]\d{0,9})$/D', $value, $match) !== 1 || !isset($match[1], $match[2])) {
            return null;
        }

        return new self($match[1], (int) $match[2]);
    }

    public function toString(): string
    {
        return $this->kind . ':' . $this->id;
    }
}
