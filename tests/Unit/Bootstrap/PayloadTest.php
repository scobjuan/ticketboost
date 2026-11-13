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

namespace GlpiPlugin\Ticketboost\Tests\Unit\Bootstrap;

use GlpiPlugin\Ticketboost\Bootstrap\Labels;
use GlpiPlugin\Ticketboost\Bootstrap\Payload;
use GlpiPlugin\Ticketboost\Config\LeaderSettings;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Keymap\ActionCatalog;
use PHPUnit\Framework\TestCase;
use stdClass;

final class PayloadTest extends TestCase
{
    private const NO_PREFERENCE = ['enabled' => true, 'leader' => [], 'keymap' => []];

    public function testDisabledWithoutTheRightOrWhenTheUserTurnedItOff(): void
    {
        $this->assertSame(['enabled' => false], Payload::build(false, new PluginConfig(), self::NO_PREFERENCE, ['help'], []));
        $this->assertSame(
            ['enabled' => false],
            Payload::build(true, new PluginConfig(), ['enabled' => false] + self::NO_PREFERENCE, ['help'], []),
        );
    }

    public function testDefaultPayload(): void
    {
        $payload = Payload::build(true, new PluginConfig(), self::NO_PREFERENCE, ['help'], ['ui.title' => 'TicketBoost']);

        $this->assertTrue($payload['enabled']);
        $this->assertSame((new LeaderSettings())->toArray(), $payload['leader']);
        $this->assertSame(ActionCatalog::defaultKeymap(), $payload['keymap']);
        $this->assertSame(['help'], $payload['available']);
        $this->assertSame(['ui.title' => 'TicketBoost'], $payload['labels']);
        $this->assertEquals(new stdClass(), $payload['options']);
    }

    public function testUserOverridesWinOverTheAdminDefaults(): void
    {
        $config     = new PluginConfig(new LeaderSettings(key: 'Alt', taps: 2), ['next' => ['KeyJ']]);
        $preference = ['enabled' => true, 'leader' => ['taps' => 3, 'mode' => 'hold'], 'keymap' => ['list' => ['KeyK']]];

        $payload = Payload::build(true, $config, $preference, [], []);

        $this->assertSame('Alt', $payload['leader']['key']);
        $this->assertSame(3, $payload['leader']['taps']);
        $this->assertSame('hold', $payload['leader']['mode']);
        $this->assertSame(['KeyJ'], $payload['keymap']['next']);
        $this->assertSame(['KeyK'], $payload['keymap']['list']);
    }

    public function testEveryLabelTheFrontendNeedsExists(): void
    {
        $labels = Labels::all();

        foreach (ActionCatalog::ids() as $id) {
            $this->assertArrayHasKey('action.' . $id, $labels);
        }

        foreach (ActionCatalog::groups() as $group) {
            $this->assertArrayHasKey('group.' . $group, $labels);
        }

        foreach (
            [
                'ui.title', 'ui.all_shortcuts', 'ui.search', 'ui.no_results', 'ui.search_failed', 'ui.hint.help',
                'ui.hint.close', 'ui.hint.back', 'ui.hint.select', 'ui.hint.toggle', 'ui.hint.confirm',
                'message.action_failed',
            ] as $key
        ) {
            $this->assertArrayHasKey($key, $labels);
        }

        $this->assertNotContains('', $labels);
    }
}
