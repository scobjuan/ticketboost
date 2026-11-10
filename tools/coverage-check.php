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

/**
 * Fails when line coverage in a Clover report is below the given minimum.
 *
 * Usage: php tools/coverage-check.php <clover.xml> <minimum percent>
 */

$file    = $argv[1] ?? '';
$minimum = (float) ($argv[2] ?? 0);

$xml = is_file($file) ? simplexml_load_file($file) : false;
if ($xml === false) {
    fwrite(STDERR, "Coverage report not found or invalid: {$file}\n");
    exit(1);
}

$metrics    = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered    = (int) $metrics['coveredstatements'];
$percent    = $statements === 0 ? 100.0 : $covered / $statements * 100;

printf("PHP line coverage: %.2f%% (%d/%d), minimum %.2f%%\n", $percent, $covered, $statements, $minimum);
exit($percent + 1e-9 < $minimum ? 1 : 0);
