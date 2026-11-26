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

namespace GlpiPlugin\Ticketboost\Tests\Unit\Config;

use GlpiPlugin\Ticketboost\Config\LeaderSettings;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Config\PreferenceForm;
use GlpiPlugin\Ticketboost\Config\PreferencePage;
use PHPUnit\Framework\TestCase;

final class PreferencePageTest extends TestCase
{
    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, array<string, mixed>>
     */
    private function actions(array $params): array
    {
        $this->assertIsArray($params['groups']);
        $actions = [];
        foreach ($params['groups'] as $group) {
            foreach ($group['actions'] as $action) {
                $actions[$action['id']] = $action;
            }
        }

        return $actions;
    }

    public function testEmptyFieldsShowTheAdministratorDefaults(): void
    {
        $admin  = new PluginConfig(leader: new LeaderSettings(key: 'Alt'), keymap: ['next' => ['KeyJ']]);
        $params = PreferencePage::params($admin, ['enabled' => true, 'leader' => [], 'keymap' => []]);
        $next   = $this->actions($params)['next'];

        $this->assertSame('KeyJ', $next['default']);
        $this->assertSame('', $next['value']);
        $this->assertFalse($next['disabled']);
        $this->assertSame($admin->leader->toArray(), $params['defaults']);
        $this->assertSame([], $params['stale']);
    }

    public function testStoredOverridesAndDisabledActionsAreShown(): void
    {
        $params  = PreferencePage::params(new PluginConfig(), [
            'enabled' => false,
            'leader'  => ['key' => 'Meta'],
            'keymap'  => ['next' => ['KeyK'], 'close' => []],
        ]);
        $actions = $this->actions($params);

        $this->assertFalse($params['enabled']);
        $this->assertSame(['key' => 'Meta'], $params['leader']);
        $this->assertSame('KeyK', $actions['next']['value']);
        $this->assertTrue($actions['close']['disabled']);
        $this->assertSame('', $actions['close']['value']);
    }

    public function testStoredShortcutsThatClashWithNewDefaultsAreFlagged(): void
    {
        $admin  = new PluginConfig(keymap: ['next' => ['KeyJ']]);
        $params = PreferencePage::params($admin, ['enabled' => true, 'leader' => [], 'keymap' => ['close' => ['KeyJ']]]);

        $this->assertNotSame([], $params['stale']);
        $this->assertNotSame([], $this->actions($params)['close']['problems']);
        $this->assertSame([], $params['issues']);
    }

    public function testARefusedFormShowsWhatWasTypedAndItsProblems(): void
    {
        $admin   = new PluginConfig();
        $refused = PreferenceForm::fromPost(['keymap' => ['next' => 'KeyP'], 'disabled' => ['close' => '1']], $admin);
        $params  = PreferencePage::params($admin, ['enabled' => true, 'leader' => [], 'keymap' => []], $refused);
        $actions = $this->actions($params);

        $this->assertFalse($params['enabled']);
        $this->assertSame('KeyP', $actions['next']['value']);
        $this->assertTrue($actions['close']['disabled']);
        $this->assertNotSame([], $params['issues']);
        $this->assertSame([], $params['stale']);
    }
}
