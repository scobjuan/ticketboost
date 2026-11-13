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

namespace GlpiPlugin\Ticketboost\Tests\Unit;

use GlpiPlugin\Ticketboost\Rights;
use PHPUnit\Framework\TestCase;

final class RightsTest extends TestCase
{
    public function testFlagsRoundTrip(): void
    {
        $this->assertSame(0, Rights::fromFlags(false, false));
        $this->assertSame(READ, Rights::fromFlags(true, false));
        $this->assertSame(READ | UPDATE, Rights::fromFlags(true, true));

        foreach ([0, READ, READ | UPDATE] as $value) {
            $flags = Rights::toFlags($value);
            $this->assertSame($value, Rights::fromFlags($flags['use'], $flags['configure']));
        }
    }

    public function testConfiguringImpliesUsing(): void
    {
        $this->assertSame(READ | UPDATE, Rights::fromFlags(false, true));
        $this->assertSame(['use' => true, 'configure' => true], Rights::toFlags(UPDATE | READ));
        $this->assertSame(['use' => false, 'configure' => false], Rights::toFlags(0));
    }
}
