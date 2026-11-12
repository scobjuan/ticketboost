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
use GlpiPlugin\Ticketboost\Keymap\KeymapResolver;
use PHPUnit\Framework\TestCase;

final class KeymapResolverTest extends TestCase
{
    public function testReturnsTheDefaultsWithoutOverrides(): void
    {
        $this->assertSame(ActionCatalog::defaultKeymap(), KeymapResolver::effective([], []));
    }

    public function testLayersReplaceWholeBindingListsAndNormalize(): void
    {
        $keymap = KeymapResolver::effective(['next' => ['KeyJ']], ['next' => ['Shift+Ctrl+KeyK'], 'close' => []]);

        $this->assertSame(['Ctrl+Shift+KeyK'], $keymap['next']);
        $this->assertSame([], $keymap['close']);
        $this->assertSame(['KeyL'], $keymap['list']);
    }

    public function testAnInvalidUserLayerIsIgnoredAsAWhole(): void
    {
        $keymap = KeymapResolver::effective(['next' => ['KeyJ']], ['next' => ['KeyK'], 'list' => ['Hyper+KeyL']]);

        $this->assertSame(['KeyJ'], $keymap['next']);
        $this->assertSame(['KeyL'], $keymap['list']);
    }

    public function testALayerThatConflictsWithTheLowerLayersIsIgnored(): void
    {
        $keymap = KeymapResolver::effective([], ['close' => ['KeyN']]);

        $this->assertSame(ActionCatalog::defaultKeymap(), $keymap);
    }

    public function testReservedUnknownAndMalformedLayersAreIgnored(): void
    {
        foreach (
            [
                ['next' => ['Escape']],
                ['reboot' => ['KeyB']],
                ['next' => 'KeyN'],
                ['next' => [42]],
                [0 => ['KeyN']],
            ] as $layer
        ) {
            $this->assertSame(ActionCatalog::defaultKeymap(), KeymapResolver::effective($layer, []));
        }
    }
}
