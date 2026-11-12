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

namespace GlpiPlugin\Ticketboost\Tests\Integration;

use Glpi\Tests\DbTestCase;
use GlpiPlugin\Ticketboost\ProfileTab;
use Profile;
use Ticket;

final class ProfileTabTest extends DbTestCase
{
    public function testRenderReflectsTheRightsAndTheEditPermission(): void
    {
        $editable = ProfileTab::render(7, ['use' => true, 'configure' => false], true);

        $this->assertStringContainsString('name="profiles_id" value="7"', $editable);
        $this->assertMatchesRegularExpression('/name="use" value="1" checked>/', $editable);
        $this->assertDoesNotMatchRegularExpression('/name="configure" value="1" checked/', $editable);
        $this->assertStringContainsString('name="update"', $editable);
        $this->assertStringNotContainsString('disabled', $editable);

        $readonly = ProfileTab::render(7, ['use' => false, 'configure' => true], false);

        $this->assertStringContainsString('disabled', $readonly);
        $this->assertStringNotContainsString('name="update"', $readonly);
    }

    public function testTheTabAppearsOnProfilesForUsersWhoCanSeeThem(): void
    {
        $this->login('glpi', 'glpi');
        $profile = getItemByTypeName(Profile::class, 'Technician');

        $this->assertSame('TicketBoost', (new ProfileTab())->getTabNameForItem($profile));
        $this->assertSame('', (new ProfileTab())->getTabNameForItem(new Ticket()));
    }

    public function testTheTabContentShowsTheStoredRight(): void
    {
        $this->login('glpi', 'glpi');
        $profile = getItemByTypeName(Profile::class, 'Super-Admin');

        ob_start();
        $shown = ProfileTab::displayTabContentForItem($profile);
        $html  = (string) ob_get_clean();

        $this->assertTrue($shown);
        $this->assertMatchesRegularExpression('/name="configure" value="1" checked>/', $html);
        $this->assertFalse(ProfileTab::displayTabContentForItem(new Ticket()));
    }
}
