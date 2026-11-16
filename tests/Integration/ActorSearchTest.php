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

use CommonITILActor;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Ticketboost\Assignment\ActorSearch;
use Group;
use Ticket;
use Ticket_User;
use User;

final class ActorSearchTest extends DbTestCase
{
    private function ticket(): int
    {
        return $this->createItem(Ticket::class, [
            'name'        => 'Search test',
            'content'     => 'Search test',
            'entities_id' => 0,
        ])->getID();
    }

    /**
     * @param array<string, string> $query
     *
     * @return list<string> The ids found.
     */
    private function ids(array $query): array
    {
        $reply = ActorSearch::respond($query);
        $this->assertTrue($reply->ok);

        /** @var list<array{id: string}> $items */
        $items = $reply->toArray()['data'];

        return array_column($items, 'id');
    }

    public function testUsersWhoCanOwnTicketsAreOffered(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();

        $found = $this->ids(['action' => 'assign_other', 'id' => (string) $id, 'q' => 'tech']);

        $this->assertContains('user:' . getItemByTypeName(User::class, 'tech', true), $found);
        $this->assertNotContains('user:' . getItemByTypeName(User::class, 'post-only', true), $this->ids(['action' => 'assign_other', 'id' => (string) $id, 'q' => 'post']));
    }

    public function testGroupsThatCanBeAssignedAreOffered(): void
    {
        $this->login('glpi', 'glpi');
        $id    = $this->ticket();
        $yes   = $this->createItem(Group::class, ['name' => 'Searchable team', 'entities_id' => 0, 'is_recursive' => 1, 'is_assign' => 1])->getID();
        $no    = $this->createItem(Group::class, ['name' => 'Searchable other', 'entities_id' => 0, 'is_recursive' => 1, 'is_assign' => 0])->getID();

        $found = $this->ids(['action' => 'assign_other', 'id' => (string) $id, 'q' => 'Searchable']);

        $this->assertContains('group:' . $yes, $found);
        $this->assertNotContains('group:' . $no, $found);
    }

    public function testPeopleAlreadyAssignedAreNotOfferedAgain(): void
    {
        $this->login('glpi', 'glpi');
        $id   = $this->ticket();
        $tech = 'user:' . getItemByTypeName(User::class, 'tech', true);
        $this->createItem(Ticket_User::class, ['tickets_id' => $id, 'users_id' => getItemByTypeName(User::class, 'tech', true), 'type' => CommonITILActor::ASSIGN]);

        $this->assertNotContains($tech, $this->ids(['action' => 'assign_other', 'id' => (string) $id, 'q' => 'tech']));
    }

    public function testTheTextIsTakenLiterally(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();

        $this->assertSame([], $this->ids(['action' => 'assign_other', 'id' => (string) $id, 'q' => '%']));
    }

    public function testCurrentAssigneesAreListedWithoutTheCurrentUser(): void
    {
        $this->login('glpi', 'glpi');
        $id      = $this->ticket();
        $tech    = getItemByTypeName(User::class, 'tech', true);
        $glpi    = getItemByTypeName(User::class, 'glpi', true);
        $this->createItem(Ticket_User::class, ['tickets_id' => $id, 'users_id' => $tech, 'type' => CommonITILActor::ASSIGN]);
        $this->createItem(Ticket_User::class, ['tickets_id' => $id, 'users_id' => $glpi, 'type' => CommonITILActor::ASSIGN]);

        $this->assertSame(['user:' . $tech], $this->ids(['action' => 'unassign_others', 'id' => (string) $id]));
        $this->assertSame([], $this->ids(['action' => 'unassign_others', 'id' => (string) $id, 'q' => 'zzz-nobody']));
    }

    public function testAnInvalidQueryIsRefused(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertSame(400, ActorSearch::respond(['action' => 'assign_me', 'id' => '1'])->status);
    }

    public function testAMissingTicketIsNotFound(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertSame(404, ActorSearch::respond(['action' => 'assign_other', 'id' => '999999'])->status);
    }

    public function testSelfServiceCannotSearch(): void
    {
        $this->login('post-only', 'postonly');
        $id = $this->createItem(Ticket::class, ['name' => 'Own', 'content' => 'Own', 'entities_id' => 0])->getID();

        $this->assertSame(403, ActorSearch::respond(['action' => 'assign_other', 'id' => (string) $id])->status);
    }
}
