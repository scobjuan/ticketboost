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

use GlpiPlugin\Ticketboost\Bootstrap\Labels;
use GlpiPlugin\Ticketboost\Keymap\ActionCatalog;
use GlpiPlugin\Ticketboost\Keymap\KeymapValidator;
use GlpiPlugin\Ticketboost\Keymap\ShortcutFields;

/**
 * What the administration page shows: the stored settings, the shortcuts by group and the
 * translated text of every problem found in a submitted form.
 *
 * @phpstan-import-type Issue from KeymapValidator
 */
final class ConfigPage
{
    /**
     * @param array<string, string> $typed  Bindings typed in a form that was refused, by action.
     * @param list<Issue>           $issues Problems found in that form.
     *
     * @return array<string, mixed> The parameters of `templates/config.html.twig`.
     */
    public static function params(PluginConfig $config, array $typed = [], array $issues = []): array
    {
        return [
            'leader'       => $config->leader->toArray(),
            'leader_keys'  => LeaderSettings::KEYS,
            'leader_modes' => self::leaderModes(),
            'close_mode'   => $config->closeMode,
            'close_modes'  => [
                PluginConfig::CLOSE_QUICK_SOLUTION => [
                    'label' => __('Quick solution', 'ticketboost'),
                    'help'  => __('Ask for the solution text and solve the ticket; GLPI then applies its own flow.', 'ticketboost'),
                ],
                PluginConfig::CLOSE_DIRECT => [
                    'label' => __('Direct close', 'ticketboost'),
                    'help'  => __('Close the ticket at once when the profile is allowed to.', 'ticketboost'),
                ],
            ],
            'groups'       => self::groups(ActionCatalog::defaultKeymap(), $typed + ShortcutFields::text($config->keymap), $issues),
            'issues'       => array_map(self::describe(...), $issues),
            'messages'     => self::messages(),
        ];
    }

    /**
     * The shortcut fields of a form, by group.
     *
     * @param array<string, list<string>>  $base     Action id to the bindings that apply when its field is empty.
     * @param array<array-key, string>     $values   Action id to the text of its field.
     * @param list<Issue>                  $issues   Problems to show under each field.
     * @param list<string>|null            $disabled Actions switched off; null when the form cannot switch them off.
     *
     * @return list<array{label: string, actions: list<array{id: string, label: string, default: string, value: string, problems: list<string>, disabled: bool|null}>}>
     */
    public static function groups(array $base, array $values, array $issues, ?array $disabled = null): array
    {
        $labels = Labels::all();
        $groups = [];
        foreach (ActionCatalog::groups() as $group) {
            $actions = [];
            foreach (ActionCatalog::ids() as $action) {
                if (ActionCatalog::groupOf($action) !== $group) {
                    continue;
                }

                $actions[] = [
                    'id'       => $action,
                    'label'    => $labels['action.' . $action] ?? $action,
                    'default'  => implode(', ', $base[$action] ?? []),
                    'value'    => $values[$action] ?? '',
                    'problems' => self::problemsOf($action, $issues),
                    'disabled' => $disabled === null ? null : in_array($action, $disabled, true),
                ];
            }

            $groups[] = ['label' => $labels['group.' . $group] ?? $group, 'actions' => $actions];
        }

        return $groups;
    }

    /**
     * @return array<string, string> Leader mode to its translated name.
     */
    public static function leaderModes(): array
    {
        return [
            'tap'  => __('Tap and release', 'ticketboost'),
            'hold' => __('Hold', 'ticketboost'),
        ];
    }

    /**
     * The texts of the live check (`ConfigMessages` in `frontend/src/config/check.ts`).
     *
     * @return array{recording: string, invalid: string, reserved: string, conflict: string, actions: array<string, string>}
     */
    public static function messages(): array
    {
        $labels  = Labels::all();
        $actions = [];
        foreach (ActionCatalog::ids() as $action) {
            $actions[$action] = $labels['action.' . $action] ?? $action;
        }

        return [
            'recording' => __('Press the new shortcut', 'ticketboost'),
            'invalid'   => __('%s is not a valid shortcut', 'ticketboost'),
            'reserved'  => __('%s is kept by the browser or GLPI and cannot be used', 'ticketboost'),
            'conflict'  => __('%1$s is already used by %2$s', 'ticketboost'),
            'actions'   => $actions,
        ];
    }

    /**
     * @param Issue $issue
     *
     * @return string The problem in the language of the user.
     */
    public static function describe(array $issue): string
    {
        $binding = $issue['binding'] ?? '';
        $others  = implode(', ', $issue['with'] ?? []);

        return match ($issue['code']) {
            KeymapValidator::UNKNOWN_ACTION   => sprintf(__('%s is not an action of TicketBoost', 'ticketboost'), $issue['action']),
            KeymapValidator::RESERVED_BINDING => sprintf(__('%s is kept by the browser or GLPI and cannot be used', 'ticketboost'), $binding),
            KeymapValidator::CONFLICT         => sprintf(__('%1$s is already used by %2$s', 'ticketboost'), $binding, $others),
            default                           => sprintf(__('%s is not a valid shortcut', 'ticketboost'), $binding),
        };
    }

    /**
     * @param list<Issue> $issues
     *
     * @return list<string>
     */
    private static function problemsOf(string $action, array $issues): array
    {
        $problems = [];
        foreach ($issues as $issue) {
            if ($issue['action'] === $action) {
                $problems[] = self::describe($issue);
            }
        }

        return $problems;
    }
}
