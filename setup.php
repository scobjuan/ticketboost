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

use GlpiPlugin\Ticketboost\Assets;
use GlpiPlugin\Ticketboost\ProfileTab;
use GlpiPlugin\Ticketboost\Rights;

// The version line is updated by the release tooling. Do not edit by hand.
/** @phpstan-ignore theCodingMachineSafe.function (constants are defined once, at plugin load) */
define('PLUGIN_TICKETBOOST_VERSION', '0.0.0'); // x-release-please-version

// Minimal GLPI version, inclusive.
/** @phpstan-ignore theCodingMachineSafe.function (constants are defined once, at plugin load) */
define('PLUGIN_TICKETBOOST_MIN_GLPI_VERSION', '11.0.0');

// Maximum GLPI version, exclusive.
/** @phpstan-ignore theCodingMachineSafe.function (constants are defined once, at plugin load) */
define('PLUGIN_TICKETBOOST_MAX_GLPI_VERSION', '12.0.99');

/**
 * Init hooks of the plugin.
 */
function plugin_init_ticketboost(): void
{
    /** @var array<string, array<string, mixed>> $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['ticketboost'] = true;

    if (!Plugin::isPluginActive('ticketboost')) {
        return;
    }

    Rights::exposeToSelfService();
    Plugin::registerClass(ProfileTab::class, ['addtabon' => Profile::class]);

    $assets = new Assets(__DIR__ . '/public');
    $PLUGIN_HOOKS['add_javascript_module']['ticketboost'] = $assets->scripts();
    $PLUGIN_HOOKS['add_css']['ticketboost']               = $assets->stylesheets();
}

/**
 * Get the name and the version of the plugin.
 *
 * @return array{
 *      name: string,
 *      version: string,
 *      author: string,
 *      license: string,
 *      homepage: string,
 *      requirements: array{glpi: array{min: string, max: string}}
 * }
 */
function plugin_version_ticketboost(): array
{
    return [
        'name'         => 'TicketBoost',
        'version'      => PLUGIN_TICKETBOOST_VERSION,
        'author'       => 'Juan A. Escobar',
        'license'      => 'GPLv3+',
        'homepage'     => 'https://github.com/scobjuan/ticketboost',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_TICKETBOOST_MIN_GLPI_VERSION,
                'max' => PLUGIN_TICKETBOOST_MAX_GLPI_VERSION,
            ],
        ],
    ];
}

/**
 * Check pre-requisites before install.
 */
function plugin_ticketboost_check_prerequisites(): bool
{
    return true;
}

/**
 * Check configuration process.
 *
 * @param bool $verbose Whether to display message on failure.
 */
function plugin_ticketboost_check_config(bool $verbose = false): bool
{
    return true;
}
