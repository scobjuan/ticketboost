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
 * It is idempotent: users and tickets are found by login and by title. It also restores the
 * starting state, so a run never inherits the leftovers of the previous one: the tickets the
 * specs created (`[tb-e2e]`) are purged, the seeded tickets get their assignee and status back and the
 * plugin settings, including the personal ones, return to their defaults.
 *
 *   tech1, tech2   Technician profile, password equal to the login
 *   tech-es        the same, with GLPI in Spanish
 *   tech-dark      the same, with a dark colour palette
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
// A technician who reads GLPI in Spanish.
(new User())->update(['id' => seed_technician('tech-es'), 'language' => 'es_ES']);
// A technician who uses a dark colour palette.
(new User())->update(['id' => seed_technician('tech-dark'), 'palette' => 'darker']);
$selfserv = seed_user_id('post-only');

/**
 * Purges the tickets the specs created, so they never reach a list a spec walks.
 */
function seed_purge_spec_tickets(): int
{
    $purged = 0;
    foreach (getAllDataFromTable('glpi_tickets', ['name' => ['LIKE', '[tb-e2e]%']]) as $row) {
        if ((new Ticket())->delete(['id' => (int) $row['id']], true)) {
            ++$purged;
        }
    }

    return $purged;
}

/**
 * Puts a seeded ticket back to its starting assignee and status.
 *
 * @param int $ticket_id Seeded ticket.
 * @param int $assignee  User assigned at seed time, 0 for an unassigned ticket.
 */
function seed_restore_ticket(int $ticket_id, int $assignee): void
{
    $link = new Ticket_User();
    foreach (getAllDataFromTable('glpi_tickets_users', ['tickets_id' => $ticket_id, 'type' => CommonITILActor::ASSIGN]) as $row) {
        if ((int) $row['users_id'] !== $assignee) {
            $link->delete(['id' => (int) $row['id']], true);
        }
    }
    if (
        $assignee > 0
        && countElementsInTable('glpi_tickets_users', ['tickets_id' => $ticket_id, 'users_id' => $assignee, 'type' => CommonITILActor::ASSIGN]) === 0
    ) {
        $link->add(['tickets_id' => $ticket_id, 'users_id' => $assignee, 'type' => CommonITILActor::ASSIGN]);
    }

    $status = $assignee > 0 ? Ticket::ASSIGNED : Ticket::INCOMING;
    $ticket = new Ticket();
    if ($ticket->getFromDB($ticket_id) && (int) $ticket->fields['status'] !== $status) {
        $ticket->update(['id' => $ticket_id, 'status' => $status]);
    }
}

$tickets = [
    1 => [$tech1, $selfserv],
    2 => [$tech1, 0],
    3 => [$tech1, 0],
    4 => [$tech2, $selfserv],
    5 => [$tech2, 0],
    6 => [0, $selfserv],
];

$purged = seed_purge_spec_tickets();
Config::deleteConfigurationValues('plugin:ticketboost', ['leader', 'keymap', 'close_mode']);
foreach (getAllDataFromTable('glpi_plugin_ticketboost_preferences') as $row) {
    (new GlpiPlugin\Ticketboost\Preference())->delete(['id' => (int) $row['id']], true);
}

foreach ($tickets as $number => [$assignee, $requester]) {
    $title = '[tb-seed] ' . $number;
    $existing = array_values(getAllDataFromTable('glpi_tickets', ['name' => $title]))[0] ?? null;
    if ($existing !== null) {
        seed_restore_ticket((int) $existing['id'], $assignee);
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

echo 'Purged ' . $purged . " spec tickets\n";
echo 'Seed ready: ' . countElementsInTable('glpi_tickets', ['name' => ['LIKE', '[tb-seed]%']]) . " tickets\n";
