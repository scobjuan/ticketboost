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

use GlpiPlugin\Ticketboost\Config\ConfigPage;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Keymap\ActionCatalog;
use PHPUnit\Framework\TestCase;

final class ConfigPageTest extends TestCase
{
    public function testEveryActionIsListedOnceUnderItsGroup(): void
    {
        $params  = ConfigPage::params(new PluginConfig());
        $listed  = [];
        foreach ($params['groups'] as $group) {
            foreach ($group['actions'] as $action) {
                $listed[] = $action['id'];
            }
        }

        $this->assertSame(ActionCatalog::ids(), $listed);
        $this->assertCount(count(ActionCatalog::groups()), $params['groups']);
    }

    public function testStoredOverridesAndDefaultsAreShown(): void
    {
        $config = new PluginConfig(keymap: ['next' => ['Ctrl+Shift+KeyK']], closeMode: 'direct_close');
        $params = ConfigPage::params($config);
        $next   = $params['groups'][0]['actions'][0];

        $this->assertSame('next', $next['id']);
        $this->assertSame('Ctrl+Shift+KeyK', $next['value']);
        $this->assertSame('KeyN, ArrowRight', $next['default']);
        $this->assertSame('direct_close', $params['close_mode']);
        $this->assertSame($config->leader->toArray(), $params['leader']);
    }

    public function testTypedTextWinsOverTheStoredOverrides(): void
    {
        $params = ConfigPage::params(new PluginConfig(keymap: ['next' => ['KeyK']]), ['next' => 'Hyper+KeyN']);

        $this->assertSame('Hyper+KeyN', $params['groups'][0]['actions'][0]['value']);
    }

    public function testEachProblemIsShownUnderItsActionAndInTheList(): void
    {
        $issues = [
            ['code' => 'conflict', 'action' => 'next', 'binding' => 'KeyP', 'with' => ['previous']],
            ['code' => 'conflict', 'action' => 'previous', 'binding' => 'KeyP', 'with' => ['next']],
        ];
        $params = ConfigPage::params(new PluginConfig(), [], $issues);
        $actions = $params['groups'][0]['actions'];

        $this->assertSame(['KeyP is already used by previous'], $actions[0]['problems']);
        $this->assertSame(['KeyP is already used by next'], $actions[1]['problems']);
        $this->assertSame([], $actions[2]['problems']);
        $this->assertCount(2, $params['issues']);
    }

    public function testTheLiveCheckGetsTheTextsAndTheActionNames(): void
    {
        $messages = ConfigPage::params(new PluginConfig())['messages'];

        $this->assertSame(ActionCatalog::ids(), array_keys($messages['actions']));
        $this->assertSame('Next ticket', $messages['actions']['next']);
        $this->assertStringContainsString('%s', $messages['invalid']);
        $this->assertStringContainsString('%1$s', $messages['conflict']);
    }

    public function testEachKindOfProblemHasItsOwnText(): void
    {
        $this->assertSame('reboot is not an action of TicketBoost', ConfigPage::describe(['code' => 'unknown_action', 'action' => 'reboot']));
        $this->assertSame('Hyper+KeyN is not a valid shortcut', ConfigPage::describe(['code' => 'invalid_binding', 'action' => 'next', 'binding' => 'Hyper+KeyN']));
        $this->assertSame(
            'Ctrl+KeyW is kept by the browser or GLPI and cannot be used',
            ConfigPage::describe(['code' => 'reserved_binding', 'action' => 'next', 'binding' => 'Ctrl+KeyW']),
        );
    }
}
