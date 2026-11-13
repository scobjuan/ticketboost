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

use Config;
use GlpiPlugin\Ticketboost\Keymap\KeymapResolver;
use stdClass;
use Throwable;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * Admin defaults, stored with GLPI's configuration API under the context
 * `plugin:ticketboost` (leader, keymap overrides and close mode).
 */
final readonly class PluginConfig
{
    public const CONTEXT = 'plugin:ticketboost';

    public const CLOSE_QUICK_SOLUTION = 'quick_solution';

    public const CLOSE_DIRECT = 'direct_close';

    public const CLOSE_MODES = [self::CLOSE_QUICK_SOLUTION, self::CLOSE_DIRECT];

    /**
     * @param array<string, list<string>> $keymap Admin overrides of the default keymap.
     */
    public function __construct(
        public LeaderSettings $leader = new LeaderSettings(),
        public array $keymap = [],
        public string $closeMode = self::CLOSE_QUICK_SOLUTION,
    ) {}

    /**
     * Builds the configuration from stored values; invalid values fall back to defaults.
     *
     * @param array<array-key, mixed> $values Stored name to value (JSON text for leader and keymap).
     */
    public static function fromValues(array $values): self
    {
        $keymap    = self::decode($values['keymap'] ?? null);
        $closeMode = $values['close_mode'] ?? null;

        return new self(
            leader: LeaderSettings::fromArray(self::decode($values['leader'] ?? null)),
            keymap: self::onlyValid($keymap),
            closeMode: is_string($closeMode) && in_array($closeMode, self::CLOSE_MODES, true)
                ? $closeMode
                : self::CLOSE_QUICK_SOLUTION,
        );
    }

    /**
     * Loads the stored configuration.
     */
    public static function load(): self
    {
        return self::fromValues(Config::getConfigurationValues(self::CONTEXT));
    }

    /**
     * Stores the configuration.
     */
    public function save(): void
    {
        Config::setConfigurationValues(self::CONTEXT, $this->toValues());
    }

    /**
     * @return array{leader: string, keymap: string, close_mode: string}
     */
    public function toValues(): array
    {
        return [
            'leader'     => json_encode($this->leader->toArray()),
            'keymap'     => json_encode($this->keymap === [] ? new stdClass() : $this->keymap),
            'close_mode' => $this->closeMode,
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function decode(mixed $json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true);
        } catch (Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<array-key, mixed> $keymap
     *
     * @return array<string, list<string>> The normalized overrides, or none when they are invalid.
     */
    private static function onlyValid(array $keymap): array
    {
        $normalized = KeymapResolver::normalizeLayer($keymap);

        return $normalized !== null && KeymapResolver::issues($normalized, []) === [] ? $normalized : [];
    }
}
