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

namespace GlpiPlugin\Ticketboost\Ticketing;

use Safe\Exceptions\JsonException;

use function Safe\json_decode;
use function Safe\preg_match;

/**
 * A validated request to `ajax/action.php` for a ticket action: change the status, change
 * the priority or close. Unknown actions and parameters are rejected, never ignored
 * (RULES.md section 6).
 */
final readonly class TicketActionRequest
{
    public const ACTIONS = ['change_status', 'change_priority', 'close'];

    /**
     * Statuses the panel may set: new, processing (assigned), processing (planned) and
     * pending. Solving and closing go through the close action.
     */
    public const STATUS_PATTERN = '/^[1-4]$/D';

    public const PRIORITY_PATTERN = '/^[1-6]$/D';

    public const MAX_SOLUTION_LENGTH = 10000;

    /**
     * @param int    $number The chosen status or priority; 0 for `close`.
     * @param string $text   The solution text of `close`; empty when none was sent.
     */
    private function __construct(
        public string $action,
        public int $id,
        public int $number,
        public string $text,
    ) {}

    public static function fromJson(string $raw): ?self
    {
        try {
            $body = json_decode($raw, true, 8);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($body) || array_diff(array_keys($body), ['action', 'id', 'value']) !== []) {
            return null;
        }

        $action = $body['action'] ?? null;
        $id     = $body['id'] ?? null;
        $value  = $body['value'] ?? null;
        if (!is_string($action) || !in_array($action, self::ACTIONS, true) || !is_int($id) || $id < 1) {
            return null;
        }

        return match ($action) {
            'change_status'   => self::number($action, $id, $value, self::STATUS_PATTERN),
            'change_priority' => self::number($action, $id, $value, self::PRIORITY_PATTERN),
            default           => self::solution($id, $value),
        };
    }

    private static function number(string $action, int $id, mixed $value, string $pattern): ?self
    {
        if (!is_string($value) || preg_match($pattern, $value) !== 1) {
            return null;
        }

        return new self($action, $id, (int) $value, '');
    }

    private static function solution(int $id, mixed $value): ?self
    {
        if ($value === null) {
            return new self('close', $id, 0, '');
        }

        if (!is_string($value) || mb_strlen($value) > self::MAX_SOLUTION_LENGTH) {
            return null;
        }

        return new self('close', $id, 0, trim($value));
    }
}
