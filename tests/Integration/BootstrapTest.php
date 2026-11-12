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
use GlpiPlugin\Ticketboost\Bootstrap\ActionAvailability;
use GlpiPlugin\Ticketboost\Bootstrap\Payload;
use GlpiPlugin\Ticketboost\Http\RequestGuard;
use GlpiPlugin\Ticketboost\Keymap\ActionCatalog;
use GlpiPlugin\Ticketboost\Preference;
use GlpiPlugin\Ticketboost\Rights;
use Profile;
use User;

final class BootstrapTest extends DbTestCase
{
    private array $server;

    public function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
    }

    public function tearDown(): void
    {
        $_SERVER = $this->server;
        parent::tearDown();
    }

    public function testSuperAdminMayUseEveryAction(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertTrue(Rights::canUse());
        $this->assertTrue(Rights::canConfigure());
        $this->assertSame(ActionCatalog::ids(), ActionAvailability::fromCapabilities(ActionAvailability::capabilities()));
    }

    public function testSelfServiceOnlySeesTheActionsItsRightsAllow(): void
    {
        $this->login('post-only', 'postonly');

        $this->assertSame(
            ['next', 'previous', 'list', 'my_tickets', 'followup', 'help'],
            ActionAvailability::fromCapabilities(ActionAvailability::capabilities()),
        );
    }

    public function testSelfServiceProfilesKeepThePluginRightInTheSession(): void
    {
        $this->login('post-only', 'postonly');

        $this->assertContains(Rights::NAME, Profile::$helpdesk_rights);
        $this->assertTrue(Rights::canUse());
        $this->assertFalse(Rights::canConfigure());
        $this->assertTrue(Payload::forCurrentUser()['enabled']);
    }

    public function testExposingTheRightTwiceDoesNotDuplicateIt(): void
    {
        Rights::exposeToSelfService();
        Rights::exposeToSelfService();

        $this->assertCount(1, array_keys(Profile::$helpdesk_rights, Rights::NAME, true));
    }

    public function testThePanelOffersOnlyImplementedActions(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertSame(ActionAvailability::IMPLEMENTED, ActionAvailability::forCurrentUser());
    }

    public function testPayloadForAnAllowedUser(): void
    {
        $this->login('glpi', 'glpi');

        $payload = Payload::forCurrentUser();

        $this->assertTrue($payload['enabled']);
        $this->assertSame(['help'], $payload['available']);
        $this->assertSame(ActionCatalog::defaultKeymap(), $payload['keymap']);
        $this->assertSame('Control', $payload['leader']['key']);
        $this->assertArrayHasKey('ui.title', $payload['labels']);
    }

    public function testPayloadIsDisabledWithoutTheProfileRight(): void
    {
        $this->login('glpi', 'glpi');
        $_SESSION['glpiactiveprofile'][Rights::NAME] = 0;

        $this->assertSame(['enabled' => false], Payload::forCurrentUser());
    }

    public function testPayloadIsDisabledWhenTheUserTurnedTheShortcutsOff(): void
    {
        $this->login('tech', 'tech');
        $this->assertTrue(Preference::saveForUser((int) getItemByTypeName(User::class, 'tech', true), false, [], []));

        $this->assertSame(['enabled' => false], Payload::forCurrentUser());
    }

    public function testPayloadAppliesTheUserPreferences(): void
    {
        $this->login('tech', 'tech');
        $this->assertTrue(Preference::saveForUser(
            (int) getItemByTypeName(User::class, 'tech', true),
            true,
            ['key' => 'Alt'],
            ['next' => ['KeyJ']],
        ));

        $payload = Payload::forCurrentUser();

        $this->assertSame('Alt', $payload['leader']['key']);
        $this->assertSame(['KeyJ'], $payload['keymap']['next']);
    }

    public function testGuardWithTheCurrentRequest(): void
    {
        $this->login('glpi', 'glpi');
        $_SERVER['REQUEST_METHOD']          = 'GET';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $this->assertNull(RequestGuard::guard('GET'));
        $this->assertNull(RequestGuard::guard('GET', RequestGuard::LEVEL_CONFIGURE));
        $this->assertSame(405, RequestGuard::guard('POST')?->status);

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $this->assertSame(400, RequestGuard::guard('GET')?->status);
    }

    public function testGuardRejectsAnonymousRequests(): void
    {
        $this->logOut();
        $_SERVER['REQUEST_METHOD']          = 'GET';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $this->assertSame(401, RequestGuard::guard('GET')?->status);
    }

    public function testGuardEnforcesThePluginRight(): void
    {
        $this->login('glpi', 'glpi');
        $_SESSION['glpiactiveprofile'][Rights::NAME] = READ;
        $_SERVER['REQUEST_METHOD']          = 'GET';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $this->assertNull(RequestGuard::guard('GET', RequestGuard::LEVEL_USE));
        $this->assertSame(403, RequestGuard::guard('GET', RequestGuard::LEVEL_CONFIGURE)?->status);

        $_SESSION['glpiactiveprofile'][Rights::NAME] = 0;
        $this->assertSame(403, RequestGuard::guard('GET', RequestGuard::LEVEL_USE)?->status);
        $this->assertNull(RequestGuard::guard('GET', RequestGuard::LEVEL_AUTHENTICATED));
    }
}
