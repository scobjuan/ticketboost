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
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Preference;
use User;

final class PreferenceTest extends DbTestCase
{
    private function userId(string $name): int
    {
        return (int) getItemByTypeName(User::class, $name, true);
    }

    public function testUsersWithoutPreferencesGetTheDefaults(): void
    {
        $this->assertSame(
            ['enabled' => true, 'leader' => [], 'keymap' => []],
            Preference::forUser($this->userId('tech')),
        );
        $this->assertSame(['enabled' => true, 'leader' => [], 'keymap' => []], Preference::forUser(0));
    }

    public function testSaveCreatesThenUpdatesTheSameRow(): void
    {
        $id = $this->userId('tech');

        $this->assertTrue(Preference::saveForUser($id, true, ['key' => 'Alt', 'taps' => 2], ['next' => ['Shift+Ctrl+KeyK']]));
        $this->assertSame(
            ['enabled' => true, 'leader' => ['key' => 'Alt', 'taps' => 2], 'keymap' => ['next' => ['Ctrl+Shift+KeyK']]],
            Preference::forUser($id),
        );

        $this->assertTrue(Preference::saveForUser($id, false, [], []));
        $this->assertSame(['enabled' => false, 'leader' => [], 'keymap' => []], Preference::forUser($id));
        $this->assertCount(1, (new Preference())->find(['users_id' => $id]));
    }

    public function testUnknownLeaderFieldsAreDropped(): void
    {
        $id = $this->userId('tech');

        $this->assertTrue(Preference::saveForUser($id, true, ['key' => 'Meta', 'evil' => 'x'], []));
        $this->assertSame(['key' => 'Meta'], Preference::forUser($id)['leader']);
    }

    public function testInvalidKeymapsAreRejectedAndNothingIsStored(): void
    {
        $id = $this->userId('tech');

        foreach (
            [
                ['next' => ['Escape']],
                ['next' => ['bogus']],
                ['reboot' => ['KeyB']],
                ['next' => 'KeyN'],
                ['close' => ['KeyN']],
            ] as $keymap
        ) {
            $this->assertFalse(Preference::saveForUser($id, true, [], $keymap), json_encode($keymap));
        }

        $this->assertCount(0, (new Preference())->find(['users_id' => $id]));
    }

    public function testConflictsWithTheAdminKeymapAreRejected(): void
    {
        (new PluginConfig(keymap: ['next' => ['KeyJ']]))->save();
        $id = $this->userId('tech');

        $this->assertFalse(Preference::saveForUser($id, true, [], ['close' => ['KeyJ']]));
        $this->assertTrue(Preference::saveForUser($id, true, [], ['close' => ['KeyN']]));
    }

    public function testInvalidUserIdIsRejected(): void
    {
        $this->assertFalse(Preference::saveForUser(0, true, [], []));
        $this->assertFalse(Preference::saveForUser(-3, true, [], []));
    }

    public function testCorruptStoredJsonFallsBackToDefaults(): void
    {
        global $DB;
        $id = $this->userId('tech');
        $DB->insert(Preference::getTable(), ['users_id' => $id, 'enabled' => 1, 'leader' => 'not json', 'keymap' => '{"next":[1,"KeyJ"],"x":"y"}']);

        $this->assertSame(
            ['enabled' => true, 'leader' => [], 'keymap' => ['next' => ['KeyJ']]],
            Preference::forUser($id),
        );
    }

    public function testTypeName(): void
    {
        $this->assertNotSame('', Preference::getTypeName());
    }
}
