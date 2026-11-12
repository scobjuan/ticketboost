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

use GlpiPlugin\Ticketboost\Bootstrap\ActionAvailability;
use GlpiPlugin\Ticketboost\Keymap\ActionCatalog;
use PHPUnit\Framework\TestCase;

final class ActionAvailabilityTest extends TestCase
{
    private const TECHNICIAN = [
        'view'     => true, 'central' => true, 'assign' => true, 'own' => true, 'update' => true,
        'solution' => true, 'priority' => true, 'followup' => true, 'task' => true,
    ];

    public function testATechnicianWithEveryRightGetsEveryAction(): void
    {
        $this->assertSame(ActionCatalog::ids(), ActionAvailability::fromCapabilities(self::TECHNICIAN));
    }

    public function testNothingButHelpWithoutAnyRight(): void
    {
        $this->assertSame(['help'], ActionAvailability::fromCapabilities([]));
    }

    public function testSelfServiceNeverGetsCentralActions(): void
    {
        $caps = ['central' => false] + self::TECHNICIAN;

        $this->assertSame(
            ['next', 'previous', 'list', 'my_tickets', 'followup', 'help'],
            ActionAvailability::fromCapabilities($caps),
        );
    }

    public function testAssignMeNeedsAssignOrOwn(): void
    {
        $base = ['central' => true, 'view' => true];

        $this->assertNotContains('assign_me', ActionAvailability::fromCapabilities($base));
        $this->assertContains('assign_me', ActionAvailability::fromCapabilities($base + ['own' => true]));
        $this->assertContains('assign_me', ActionAvailability::fromCapabilities($base + ['assign' => true]));
        $this->assertNotContains('assign_other', ActionAvailability::fromCapabilities($base + ['own' => true]));
    }

    public function testCloseAcceptsUpdateOrSolutionRight(): void
    {
        $base = ['central' => true];

        $this->assertNotContains('close', ActionAvailability::fromCapabilities($base));
        $this->assertContains('close', ActionAvailability::fromCapabilities($base + ['solution' => true]));
        $this->assertContains('close', ActionAvailability::fromCapabilities($base + ['update' => true]));
    }

    public function testOnlyImplementedActionsAreOffered(): void
    {
        $this->assertSame(['help'], ActionAvailability::IMPLEMENTED);
        foreach (ActionAvailability::IMPLEMENTED as $action) {
            $this->assertTrue(ActionCatalog::exists($action));
        }
    }
}
