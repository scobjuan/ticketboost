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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$baseline_file = __DIR__ . '/../../PluginsPhpCsFixer.php';
if (!file_exists($baseline_file)) {
    throw new RuntimeException(sprintf(
        'Unable to find "%s". php-cs-fixer must run inside a GLPI development checkout (use `make lint-php`).',
        $baseline_file,
    ));
}

$baseline = require $baseline_file;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['node_modules', 'vendor', 'var', 'public', 'docs', 'e2e', 'frontend'])
    ->ignoreVCSIgnored(true)
    ->name('*.php');

/** @var Config $config */
$config = $baseline($finder);

return $config
    ->setCacheFile(__DIR__ . '/var/php-cs-fixer/.php-cs-fixer.cache')
;
