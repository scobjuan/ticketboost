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

namespace GlpiPlugin\Ticketboost\Config;

use function Safe\preg_match;

/**
 * Leader key settings (PLAN.md section 3.1), always within safe bounds.
 *
 * Mirrors `LeaderConfig` in `frontend/src/keys/leader.ts`.
 */
final readonly class LeaderSettings
{
    public const KEYS = ['Control', 'Alt', 'Shift', 'Meta'];

    public const MODES = ['tap', 'hold'];

    public function __construct(
        public string $key = 'Control',
        public string $mode = 'tap',
        public int $taps = 1,
        public int $tapMaxMs = 300,
        public int $tapWindowMs = 400,
        public int $holdMs = 500,
    ) {}

    /**
     * Builds settings from untrusted input; anything invalid falls back to the default
     * and numbers are clamped.
     *
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $defaults = new self();
        $key      = $data['key'] ?? null;
        $mode     = $data['mode'] ?? null;

        return new self(
            key: is_string($key) && in_array($key, self::KEYS, true) ? $key : $defaults->key,
            mode: is_string($mode) && in_array($mode, self::MODES, true) ? $mode : $defaults->mode,
            taps: self::clamp($data['taps'] ?? null, 1, 3, $defaults->taps),
            tapMaxMs: self::clamp($data['tapMaxMs'] ?? null, 100, 1000, $defaults->tapMaxMs),
            tapWindowMs: self::clamp($data['tapWindowMs'] ?? null, 100, 1000, $defaults->tapWindowMs),
            holdMs: self::clamp($data['holdMs'] ?? null, 200, 2000, $defaults->holdMs),
        );
    }

    /**
     * @return array{key: string, mode: string, taps: int, tapMaxMs: int, tapWindowMs: int, holdMs: int}
     */
    public function toArray(): array
    {
        return [
            'key'         => $this->key,
            'mode'        => $this->mode,
            'taps'        => $this->taps,
            'tapMaxMs'    => $this->tapMaxMs,
            'tapWindowMs' => $this->tapWindowMs,
            'holdMs'      => $this->holdMs,
        ];
    }

    private static function clamp(mixed $value, int $min, int $max, int $default): int
    {
        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            $value = (int) $value;
        }

        if (!is_int($value)) {
            return $default;
        }

        return max($min, min($max, $value));
    }
}
