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

use GlpiPlugin\Ticketboost\Keymap\KeymapResolver;
use GlpiPlugin\Ticketboost\Keymap\KeymapValidator;
use GlpiPlugin\Ticketboost\Keymap\ShortcutFields;

/**
 * The personal settings a user submits, checked against the administrator defaults before
 * they are stored. Only what differs from those defaults is kept, so later changes of the
 * administrator still reach the user.
 *
 * @phpstan-import-type Issue from KeymapValidator
 */
final readonly class PreferenceForm
{
    /**
     * @param array<string, int|string>      $leader Leader fields the user overrides.
     * @param array<array-key, list<string>> $keymap Keymap overrides as submitted, normalized when valid.
     * @param list<Issue>                    $issues What is wrong with the keymap; empty when it can be stored.
     */
    public function __construct(
        public bool $enabled,
        public array $leader,
        public array $keymap,
        public array $issues,
    ) {}

    /**
     * Reads the submitted form. An empty leader field follows the administrator; numbers are
     * clamped and unknown values are ignored. A shortcut that cannot be used is reported.
     *
     * @param array<array-key, mixed> $post  The form fields.
     * @param PluginConfig            $admin The administrator defaults.
     */
    public static function fromPost(array $post, PluginConfig $admin): self
    {
        $off      = $post['disabled'] ?? null;
        $disabled = is_array($off) ? array_values(array_filter(array_keys($off), is_string(...))) : [];
        $layer    = ShortcutFields::layer($post['keymap'] ?? null, KeymapResolver::effective($admin->keymap, []), $disabled);

        $normalized = KeymapResolver::normalizeLayer($layer);
        $issues     = $normalized === null
            ? ShortcutFields::invalidIssues($layer)
            : KeymapResolver::issues($admin->keymap, $normalized);

        return new self(
            enabled: isset($post['enabled']),
            leader: self::leaderOverrides($post['leader'] ?? null),
            keymap: $issues === [] && $normalized !== null ? $normalized : $layer,
            issues: $issues,
        );
    }

    public function isValid(): bool
    {
        return $this->issues === [];
    }

    /**
     * @return array<string, int|string>
     */
    private static function leaderOverrides(mixed $fields): array
    {
        $overrides = [];
        foreach (is_array($fields) ? $fields : [] as $name => $value) {
            if (!is_string($name) || !is_string($value) || $value === '') {
                continue;
            }

            if (
                ($name === 'key' && !in_array($value, LeaderSettings::KEYS, true))
                || ($name === 'mode' && !in_array($value, LeaderSettings::MODES, true))
                || (!in_array($name, ['key', 'mode'], true) && !is_numeric($value))
            ) {
                continue;
            }

            $clamped = LeaderSettings::fromArray([$name => $value])->toArray();
            if (array_key_exists($name, $clamped)) {
                $overrides[$name] = $clamped[$name];
            }
        }

        return $overrides;
    }
}
