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

namespace GlpiPlugin\Ticketboost\Tests\Unit\Keymap;

use GlpiPlugin\Ticketboost\Keymap\KeymapResolver;
use PHPUnit\Framework\TestCase;

final class KeymapResolverLayersTest extends TestCase
{
    public function testNormalizeLayer(): void
    {
        $this->assertSame(['next' => ['Ctrl+Shift+KeyK']], KeymapResolver::normalizeLayer(['next' => ['Shift+Ctrl+KeyK']]));
        $this->assertSame([], KeymapResolver::normalizeLayer([]));
        $this->assertNull(KeymapResolver::normalizeLayer(['next' => 'KeyN']));
        $this->assertNull(KeymapResolver::normalizeLayer(['next' => ['bogus']]));
        $this->assertNull(KeymapResolver::normalizeLayer([0 => ['KeyN']]));
    }

    public function testIssuesOfValidLayersAreEmpty(): void
    {
        $this->assertSame([], KeymapResolver::issues([], []));
        $this->assertSame([], KeymapResolver::issues(['next' => ['KeyJ']], ['list' => ['KeyK']]));
    }

    public function testIssuesReportConflictsAcrossLayers(): void
    {
        $issues = KeymapResolver::issues(['next' => ['KeyJ']], ['close' => ['KeyJ']]);

        $this->assertSame(['conflict', 'conflict'], array_column($issues, 'code'));
        $this->assertSame(['next', 'close'], array_column($issues, 'action'));
    }

    public function testIssuesReportMalformedAndUnknownLayers(): void
    {
        $this->assertSame('invalid_binding', KeymapResolver::issues(['next' => 'KeyN'], [])[0]['code']);
        $this->assertSame('invalid_binding', KeymapResolver::issues([], ['next' => ['bogus']])[0]['code']);
        $this->assertSame('unknown_action', KeymapResolver::issues([], ['reboot' => ['KeyB']])[0]['code']);
        $this->assertSame('reserved_binding', KeymapResolver::issues([], ['next' => ['Escape']])[0]['code']);
    }
}
