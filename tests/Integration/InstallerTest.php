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

use Config;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Install\Installer;
use GlpiPlugin\Ticketboost\Preference;
use GlpiPlugin\Ticketboost\Rights;
use PHPUnit\Framework\TestCase;
use Profile;
use ProfileRight;

/**
 * Installs and uninstalls for real. DDL commits implicitly, so this test does not use a
 * transaction and always leaves the plugin installed.
 */
final class InstallerTest extends TestCase
{
    private Installer $installer;

    protected function setUp(): void
    {
        global $DB;
        $this->installer = new Installer($DB);
    }

    protected function tearDown(): void
    {
        $this->installer->install();
    }

    private function profileId(string $name): int
    {
        return (int) getItemByTypeName(Profile::class, $name, true);
    }

    private function storedRight(int $profiles_id): int
    {
        return (int) (ProfileRight::getProfileRights($profiles_id, [Rights::NAME])[Rights::NAME] ?? -1);
    }

    public function testInstallCreatesTableRightsAndConfig(): void
    {
        global $DB;

        $this->assertTrue($DB->tableExists('glpi_plugin_ticketboost_preferences', false));
        $this->assertSame('glpi_plugin_ticketboost_preferences', Preference::getTable());
        $this->assertSame(READ | UPDATE, $this->storedRight($this->profileId('Super-Admin')));
        $this->assertSame(READ, $this->storedRight($this->profileId('Self-Service')));
        $stored = array_keys(Config::getConfigurationValues(PluginConfig::CONTEXT));
        sort($stored);
        $this->assertSame(['close_mode', 'keymap', 'leader'], $stored);
        $this->assertEquals(new PluginConfig(), PluginConfig::load());
    }

    public function testEveryProfileGetsTheRight(): void
    {
        global $DB;

        $profiles = count($DB->request(['FROM' => 'glpi_profiles']));
        $rights   = count($DB->request(['FROM' => 'glpi_profilerights', 'WHERE' => ['name' => Rights::NAME]]));

        $this->assertGreaterThan(0, $profiles);
        $this->assertSame($profiles, $rights);
    }

    public function testUninstallRemovesEverythingAndInstallRestoresIt(): void
    {
        global $DB;

        $this->assertTrue($this->installer->uninstall());

        $this->assertFalse($DB->tableExists('glpi_plugin_ticketboost_preferences', false));
        $this->assertCount(0, $DB->request(['FROM' => 'glpi_profilerights', 'WHERE' => ['name' => Rights::NAME]]));
        $this->assertSame([], Config::getConfigurationValues(PluginConfig::CONTEXT));

        $this->assertTrue($this->installer->install());

        $this->assertTrue($DB->tableExists('glpi_plugin_ticketboost_preferences', false));
        $this->assertSame(READ | UPDATE, $this->storedRight($this->profileId('Super-Admin')));
        $this->assertNotSame([], Config::getConfigurationValues(PluginConfig::CONTEXT));
    }

    public function testInstallTwiceKeepsWhatTheAdministratorChose(): void
    {
        $self_service = $this->profileId('Self-Service');
        ProfileRight::updateProfileRights($self_service, [Rights::NAME => 0]);
        (new PluginConfig(closeMode: PluginConfig::CLOSE_DIRECT))->save();

        $this->assertTrue($this->installer->install());

        $this->assertSame(0, $this->storedRight($self_service));
        $this->assertSame(PluginConfig::CLOSE_DIRECT, PluginConfig::load()->closeMode);

        ProfileRight::updateProfileRights($self_service, [Rights::NAME => READ]);
        (new PluginConfig())->save();
    }

    public function testUninstallTwiceIsHarmless(): void
    {
        $this->assertTrue($this->installer->uninstall());
        $this->assertTrue($this->installer->uninstall());
    }
}
