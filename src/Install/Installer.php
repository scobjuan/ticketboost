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

namespace GlpiPlugin\Ticketboost\Install;

use Config;
use DBConnection;
use DBmysql;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Preference;
use GlpiPlugin\Ticketboost\Rights;
use ProfileRight;

/**
 * Creates and removes everything the plugin stores in the GLPI database. Both directions
 * are idempotent, so a failed or repeated run is safe.
 *
 * The table is created with DDL through `DBmysql::doQuery()`, the documented way for
 * plugins; every other access uses the GLPI API.
 */
final readonly class Installer
{
    public function __construct(private DBmysql $db) {}

    public function install(): bool
    {
        $this->createPreferencesTable();
        $this->createRights();
        $this->createConfig();

        return true;
    }

    public function uninstall(): bool
    {
        $this->db->doQuery('DROP TABLE IF EXISTS ' . $this->db::quoteName(Preference::getTable()));
        ProfileRight::deleteProfileRights([Rights::NAME]);
        Config::deleteConfigurationValues(
            PluginConfig::CONTEXT,
            array_keys(Config::getConfigurationValues(PluginConfig::CONTEXT)),
        );

        return true;
    }

    private function createPreferencesTable(): void
    {
        $table = Preference::getTable();
        // Not cached: uninstall and install can run in the same request.
        if ($this->db->tableExists($table, false)) {
            return;
        }

        $charset   = DBConnection::getDefaultCharset();
        $collation = DBConnection::getDefaultCollation();
        $sign      = DBConnection::getDefaultPrimaryKeySignOption();

        $this->db->doQuery(
            'CREATE TABLE ' . $this->db::quoteName($table) . " (
                `id` int {$sign} NOT NULL AUTO_INCREMENT,
                `users_id` int {$sign} NOT NULL DEFAULT 0,
                `enabled` tinyint NOT NULL DEFAULT 1,
                `leader` text DEFAULT NULL,
                `keymap` text DEFAULT NULL,
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `users_id` (`users_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC",
        );
    }

    /**
     * Adds the right to every profile: READ for all, READ and UPDATE for the profiles that
     * can already change the GLPI configuration. An existing right is left untouched, so a
     * reinstall never overrides what an administrator chose.
     */
    private function createRights(): void
    {
        $exists = count($this->db->request(['FROM' => 'glpi_profilerights', 'WHERE' => ['name' => Rights::NAME]])) > 0;
        if ($exists) {
            return;
        }

        ProfileRight::addProfileRights([Rights::NAME]);

        $admins = [];
        foreach ($this->db->request(['FROM' => 'glpi_profilerights', 'WHERE' => ['name' => 'config']]) as $row) {
            if (is_array($row) && (Rights::toInt($row['rights'] ?? 0) & UPDATE) === UPDATE) {
                $admins[Rights::toInt($row['profiles_id'] ?? 0)] = true;
            }
        }

        foreach ($this->db->request(['SELECT' => ['id'], 'FROM' => 'glpi_profiles']) as $profile) {
            $id = is_array($profile) ? Rights::toInt($profile['id'] ?? 0) : 0;
            $this->db->update(
                'glpi_profilerights',
                ['rights' => Rights::fromFlags(true, isset($admins[$id]))],
                ['profiles_id' => $id, 'name' => Rights::NAME],
            );
        }
    }

    /**
     * Stores the default configuration for the values that are not set yet.
     */
    private function createConfig(): void
    {
        $stored   = Config::getConfigurationValues(PluginConfig::CONTEXT);
        $defaults = (new PluginConfig())->toValues();

        Config::setConfigurationValues(PluginConfig::CONTEXT, array_diff_key($defaults, $stored));
    }
}
