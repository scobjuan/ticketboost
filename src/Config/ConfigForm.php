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

use function Safe\preg_split;

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
        $layer  = self::keymapLayer($post['keymap'] ?? null);

        $normalized = KeymapResolver::normalizeLayer($layer);
        $issues     = $normalized === null
            ? self::invalidIssues($layer)
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
        $text = [];
        foreach (self::keymapLayer($post['keymap'] ?? null) as $action => $bindings) {
            $text[$action] = implode(', ', $bindings);
        }

        return $text;
    }

    /**
     * Turns the `keymap[action]` fields into an override layer: an empty field keeps the default
     * and a field equal to the default stores nothing.
     *
     * @return array<array-key, list<string>>
     */
    private static function keymapLayer(mixed $fields): array
    {
        $layer    = [];
        $defaults = ActionCatalog::defaultKeymap();
        $fields   = is_array($fields) ? $fields : [];
        foreach ($fields as $action => $text) {
            if (!is_string($action) || !is_string($text)) {
                continue;
            }

            $bindings = array_values(array_filter(
                preg_split('/[\s,]+/', $text, -1, PREG_SPLIT_NO_EMPTY),
                is_string(...),
            ));
            if ($bindings === [] || $bindings === ($defaults[$action] ?? [])) {
                continue;
            }

            $layer[$action] = $bindings;
        }

        return $layer;
    }

    /**
     * Names the bindings that do not parse, so the form can point at them.
     *
     * @param array<array-key, list<string>> $layer
     *
     * @return list<Issue>
     */
    private static function invalidIssues(array $layer): array
    {
        $issues = [];
        foreach ($layer as $action => $bindings) {
            foreach ($bindings as $binding) {
                if (KeymapValidator::normalize($binding) === null) {
                    $issues[] = ['code' => KeymapValidator::INVALID_BINDING, 'action' => (string) $action, 'binding' => $binding];
                }
            }
        }

        return $issues;
    }
}
