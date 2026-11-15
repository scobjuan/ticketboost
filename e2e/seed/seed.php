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
 * Seeds the data the e2e specs rely on. Run it inside a GLPI container:
 *   php /var/www/glpi/plugins/ticketboost/e2e/seed/seed.php
 *
 * It is idempotent: users and tickets are found by login and by title.
 *
 *   tech1, tech2   Technician profile, password equal to the login
 *   [tb-seed] 1-6  tickets; 1-3 assigned to tech1, 4-5 to tech2, 6 unassigned;
 *                  1, 4 and 6 requested by the self-service account (post-only)
 */

require '/var/www/glpi/vendor/autoload.php';

$kernel = new Glpi\Kernel\Kernel('production');
$kernel->boot();

global $DB;

// Act as the super-admin so that business rules and entity checks see a session.
$_SESSION['glpiID']                   = 2;
$_SESSION['glpiname']                 = 'glpi';
$_SESSION['glpiactive_entity']        = 0;
$_SESSION['glpiactiveentities']       = [0];
$_SESSION['glpiactiveentities_string'] = '0';
$_SESSION['glpiactive_entity_recursive'] = true;
$_SESSION['glpi_currenttime']         = date('Y-m-d H:i:s');

function seed_user_id(string $login): int
{
    foreach (getAllDataFromTable('glpi_users', ['name' => $login]) as $row) {
        return (int) $row['id'];
    }

    return 0;
}

function seed_technician(string $login): int
{
    $profile = array_values(getAllDataFromTable('glpi_profiles', ['name' => 'Technician']))[0] ?? null;
    if ($profile === null) {
        fwrite(STDERR, "Technician profile not found\n");
        exit(1);
    }
    $profile_id = (int) $profile['id'];

    $id = seed_user_id($login);
    if ($id === 0) {
        $id = (int) (new User())->add([
            'name'      => $login,
            'password'  => $login,
            'password2' => $login,
            'is_active' => 1,
        ]);
    }

    if (countElementsInTable('glpi_profiles_users', ['users_id' => $id, 'profiles_id' => $profile_id]) === 0) {
        (new Profile_User())->add([
            'users_id'     => $id,
            'profiles_id'  => $profile_id,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);
    }

    // New users also receive the default self-service profile: make Technician the one used at login.
    (new User())->update(['id' => $id, 'profiles_id' => $profile_id]);

    return $id;
}

$tech1    = seed_technician('tech1');
$tech2    = seed_technician('tech2');
$selfserv = seed_user_id('post-only');

$tickets = [
    1 => [$tech1, $selfserv],
    2 => [$tech1, 0],
    3 => [$tech1, 0],
    4 => [$tech2, $selfserv],
    5 => [$tech2, 0],
    6 => [0, $selfserv],
];

foreach ($tickets as $number => [$assignee, $requester]) {
    $title = '[tb-seed] ' . $number;
    if (countElementsInTable('glpi_tickets', ['name' => $title]) > 0) {
        continue;
    }

    $input = [
        'name'        => $title,
        'content'     => 'Ticket ' . $number . ' seeded for the TicketBoost e2e specs.',
        'entities_id' => 0,
    ];
    if ($assignee > 0) {
        $input['_users_id_assign'] = $assignee;
    }
    if ($requester > 0) {
        $input['_users_id_requester'] = $requester;
    }

    $ticket = new Ticket();
    if ($ticket->add($input) === false) {
        fwrite(STDERR, 'Could not create ' . $title . "\n");
        exit(1);
    }
}

echo 'Seed ready: ' . countElementsInTable('glpi_tickets', ['name' => ['LIKE', '[tb-seed]%']]) . " tickets\n";
