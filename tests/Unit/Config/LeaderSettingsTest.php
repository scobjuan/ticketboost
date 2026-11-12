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
use PHPUnit\Framework\TestCase;

final class LeaderSettingsTest extends TestCase
{
    public function testDefaultsMatchThePlan(): void
    {
        $this->assertSame(
            ['key' => 'Control', 'mode' => 'tap', 'taps' => 1, 'tapMaxMs' => 300, 'tapWindowMs' => 400, 'holdMs' => 500],
            (new LeaderSettings())->toArray(),
        );
    }

    public function testValidInputIsKept(): void
    {
        $settings = LeaderSettings::fromArray(
            ['key' => 'Alt', 'mode' => 'hold', 'taps' => 2, 'tapMaxMs' => 250, 'tapWindowMs' => 500, 'holdMs' => 800],
        );

        $this->assertSame(
            ['key' => 'Alt', 'mode' => 'hold', 'taps' => 2, 'tapMaxMs' => 250, 'tapWindowMs' => 500, 'holdMs' => 800],
            $settings->toArray(),
        );
    }

    public function testInvalidInputFallsBackToTheDefaults(): void
    {
        $settings = LeaderSettings::fromArray(
            ['key' => 'Enter', 'mode' => 'chord', 'taps' => 'many', 'tapMaxMs' => null, 'tapWindowMs' => 1.5],
        );

        $this->assertEquals(new LeaderSettings(), $settings);
        $this->assertEquals(new LeaderSettings(), LeaderSettings::fromArray(['key' => ['Alt']]));
    }

    public function testNumbersAreClampedAndNumericStringsAccepted(): void
    {
        $settings = LeaderSettings::fromArray(['taps' => 9, 'tapMaxMs' => '5', 'tapWindowMs' => 99999, 'holdMs' => '1500']);

        $this->assertSame(3, $settings->taps);
        $this->assertSame(100, $settings->tapMaxMs);
        $this->assertSame(1000, $settings->tapWindowMs);
        $this->assertSame(1500, $settings->holdMs);
        $this->assertSame(1, LeaderSettings::fromArray(['taps' => -4])->taps);
    }
}
