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

use GlpiPlugin\Ticketboost\Keymap\ActionCatalog;
use GlpiPlugin\Ticketboost\Keymap\KeymapResolver;
use GlpiPlugin\Ticketboost\Keymap\KeymapValidator;
use GlpiPlugin\Ticketboost\Keymap\ShortcutFields;

/**
 * The configuration an administrator submits, checked before it is stored.
 *
 * @phpstan-import-type Issue from KeymapValidator
 */
final readonly class ConfigForm
{
    /**
     * @param list<Issue> $issues What is wrong with the submitted keymap; empty when it can be stored.
     */
    public function __construct(
        public PluginConfig $config,
        public array $issues,
    ) {}

    /**
     * Reads the submitted form. Leader numbers are clamped and unknown values fall back to the
     * defaults; the keymap is not: a binding that cannot be used is reported, never dropped.
     *
     * @param array<array-key, mixed> $post The form fields.
     */
    public static function fromPost(array $post): self
    {
        $leader = $post['leader'] ?? null;
        $mode   = $post['close_mode'] ?? null;
        $layer  = ShortcutFields::layer($post['keymap'] ?? null, ActionCatalog::defaultKeymap());

        $normalized = KeymapResolver::normalizeLayer($layer);
        $issues     = $normalized === null
            ? ShortcutFields::invalidIssues($layer)
            : KeymapResolver::issues($normalized, []);

        return new self(
            new PluginConfig(
                leader: LeaderSettings::fromArray(is_array($leader) ? $leader : []),
                keymap: $issues === [] && $normalized !== null ? $normalized : [],
                closeMode: is_string($mode) && in_array($mode, PluginConfig::CLOSE_MODES, true)
                    ? $mode
                    : PluginConfig::CLOSE_QUICK_SOLUTION,
            ),
            $issues,
        );
    }

    public function isValid(): bool
    {
        return $this->issues === [];
    }

    /**
     * The overrides to show for each action: what was typed, or what is stored.
     *
     * @param array<array-key, mixed> $post The form fields.
     *
     * @return array<array-key, string> Action id to the bindings as text, `Ctrl+KeyK, KeyN`.
     */
    public static function typed(array $post): array
    {
        return ShortcutFields::text(ShortcutFields::layer($post['keymap'] ?? null, ActionCatalog::defaultKeymap()));
    }
}
