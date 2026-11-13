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

use GlpiPlugin\Ticketboost\Keymap\ActionCatalog;
use PHPUnit\Framework\TestCase;

final class ActionCatalogTest extends TestCase
{
    public function testEveryActionHasAKnownGroupAndContext(): void
    {
        foreach (ActionCatalog::ids() as $id) {
            $this->assertContains(ActionCatalog::groupOf($id), ActionCatalog::groups(), $id);
            $this->assertContains(ActionCatalog::contextOf($id), ['ticket', 'anywhere'], $id);
        }
    }

    public function testUnknownActions(): void
    {
        $this->assertFalse(ActionCatalog::exists('format_disk'));
        $this->assertNull(ActionCatalog::groupOf('format_disk'));
        $this->assertNull(ActionCatalog::contextOf('format_disk'));
        $this->assertTrue(ActionCatalog::exists('close'));
    }

    public function testHelpAndMyTicketsWorkAnywhere(): void
    {
        $this->assertSame('anywhere', ActionCatalog::contextOf('help'));
        $this->assertSame('anywhere', ActionCatalog::contextOf('my_tickets'));
        $this->assertSame('ticket', ActionCatalog::contextOf('close'));
    }
}
