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

use Safe\Exceptions\JsonException;

use function Safe\json_decode;

/**
 * A validated request to `ajax/action.php` for an assignment action. Unknown actions and
 * parameters are rejected, never ignored (RULES.md section 6).
 */
final readonly class AssignmentRequest
{
    public const ACTIONS = ['assign_me', 'assign_other', 'unassign_me', 'unassign_others'];

    /**
     * Most assignees one request may remove.
     */
    public const MAX_ACTORS = 50;

    /**
     * @param list<Actor> $actors The chosen assignees: none, one for `assign_other`, several for `unassign_others`.
     */
    private function __construct(
        public string $action,
        public int $id,
        public array $actors,
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
        if (!is_string($action) || !in_array($action, self::ACTIONS, true) || !is_int($id) || $id < 1) {
            return null;
        }

        $actors = self::actors($action, $body['value'] ?? null);

        return $actors === null ? null : new self($action, $id, $actors);
    }

    /**
     * @return list<Actor>|null Null when the value does not fit the action.
     */
    private static function actors(string $action, mixed $value): ?array
    {
        if ($action === 'assign_other') {
            $actor = Actor::fromString($value);

            return $actor instanceof Actor ? [$actor] : null;
        }

        if ($action !== 'unassign_others') {
            return $value === null ? [] : null;
        }

        if (!is_array($value) || $value === [] || count($value) > self::MAX_ACTORS) {
            return null;
        }

        $actors = [];
        foreach ($value as $text) {
            $actor = Actor::fromString($text);
            if (!$actor instanceof Actor) {
                return null;
            }

            $actors[$actor->toString()] = $actor;
        }

        return array_values($actors);
    }
}
