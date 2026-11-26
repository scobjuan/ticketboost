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
use GlpiPlugin\Ticketboost\Preference;
use GlpiPlugin\Ticketboost\PreferenceTab;
use GlpiPlugin\Ticketboost\Rights;
use Preference as UserSettings;
use Session;
use Ticket;
use User;

final class PreferenceTabTest extends DbTestCase
{
    private function userId(string $name): int
    {
        return (int) getItemByTypeName(User::class, $name, true);
    }

    private function user(string $name): User
    {
        $user = new User();
        $this->assertTrue($user->getFromDB($this->userId($name)));

        return $user;
    }

    public function testUsersManageTheirOwnSettings(): void
    {
        $this->login('tech', 'tech');

        $this->assertTrue(PreferenceTab::canManage($this->userId('tech')));
        $this->assertSame('TicketBoost', (new PreferenceTab())->getTabNameForItem(new UserSettings()));
        $this->assertSame('TicketBoost', (new PreferenceTab())->getTabNameForItem($this->user('tech')));
    }

    public function testSelfServiceUsersManageTheirOwnSettings(): void
    {
        $this->login('post-only', 'postonly');

        $this->assertTrue(PreferenceTab::canManage($this->userId('post-only')));
    }

    public function testAProfileThatCannotUseThePluginGetsNoTab(): void
    {
        $this->login('tech', 'tech');
        $_SESSION['glpiactiveprofile'][Rights::NAME] = 0;

        $this->assertFalse(PreferenceTab::canManage($this->userId('tech')));
        $this->assertSame('', (new PreferenceTab())->getTabNameForItem(new UserSettings()));
    }

    public function testChangingSomebodyElseNeedsTheConfigureRight(): void
    {
        $this->login('tech', 'tech');

        $this->assertFalse(PreferenceTab::canManage($this->userId('glpi')));
        $this->assertFalse(PreferenceTab::canManage($this->userId('normal')));

        $this->login('glpi', 'glpi');

        $this->assertTrue(PreferenceTab::canManage($this->userId('tech')));

        $_SESSION['glpiactiveprofile'][Rights::NAME] = READ;

        $this->assertFalse(PreferenceTab::canManage($this->userId('tech')));
    }

    public function testUnknownUsersAndOtherItemsGetNothing(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertFalse(PreferenceTab::canManage(0));
        $this->assertFalse(PreferenceTab::canManage(999999));
        $this->assertSame('', (new PreferenceTab())->getTabNameForItem(new Ticket()));
        $this->assertFalse(PreferenceTab::displayTabContentForItem(new Ticket()));
    }

    public function testTheTabShowsTheStoredSettings(): void
    {
        $this->login('tech', 'tech');
        $id = (int) Session::getLoginUserID();
        $this->assertTrue(Preference::saveForUser($id, true, ['key' => 'Alt'], ['next' => ['KeyJ'], 'close' => []]));

        ob_start();
        $shown = PreferenceTab::displayTabContentForItem(new UserSettings());
        $html  = (string) ob_get_clean();

        $this->assertTrue($shown);
        $this->assertStringContainsString('name="users_id" value="' . $id . '"', $html);
        $this->assertMatchesRegularExpression('/name="keymap\[next\]" value="KeyJ"/', $html);
        $this->assertMatchesRegularExpression('/name="disabled\[close\]" value="1" checked/', $html);
        $this->assertMatchesRegularExpression('/<option value="Alt" selected>/', $html);
        $this->assertStringContainsString('ticketboost-config.js', $html);
    }
}
