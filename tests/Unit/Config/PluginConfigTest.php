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

use GlpiPlugin\Ticketboost\Config\PluginConfig;
use PHPUnit\Framework\TestCase;

final class PluginConfigTest extends TestCase
{
    public function testDefaultsWhenNothingIsStored(): void
    {
        $config = PluginConfig::fromValues([]);

        $this->assertSame('Control', $config->leader->key);
        $this->assertSame([], $config->keymap);
        $this->assertSame('quick_solution', $config->closeMode);
    }

    public function testStoredValuesRoundTrip(): void
    {
        $config = PluginConfig::fromValues([
            'leader'     => '{"key":"Alt","taps":2}',
            'keymap'     => '{"next":["Shift+Ctrl+KeyK"]}',
            'close_mode' => 'direct_close',
        ]);

        $this->assertSame('Alt', $config->leader->key);
        $this->assertSame(2, $config->leader->taps);
        $this->assertSame(['next' => ['Ctrl+Shift+KeyK']], $config->keymap);
        $this->assertSame('direct_close', $config->closeMode);
        $this->assertEquals($config, PluginConfig::fromValues($config->toValues()));
    }

    public function testDefaultsSerializeToJsonObjects(): void
    {
        $values = (new PluginConfig())->toValues();

        $this->assertSame('{}', $values['keymap']);
        $this->assertStringStartsWith('{"key":"Control"', $values['leader']);
        $this->assertSame('quick_solution', $values['close_mode']);
    }

    public function testInvalidStoredValuesFallBackToDefaults(): void
    {
        foreach (
            [
                ['leader' => 'not json', 'keymap' => '[1,2]', 'close_mode' => 'explode'],
                ['leader' => 42, 'keymap' => null, 'close_mode' => ['x']],
                ['keymap' => '{"next":["Escape"]}'],
                ['keymap' => '{"close":["KeyN"]}'],
                ['keymap' => '{"reboot":["KeyB"]}'],
            ] as $values
        ) {
            $config = PluginConfig::fromValues($values);

            $this->assertEquals(new PluginConfig(), $config, json_encode($values));
        }
    }
}
