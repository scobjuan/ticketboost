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
use GlpiPlugin\Ticketboost\Assignment\Actor;
use GlpiPlugin\Ticketboost\Assignment\Assignees;
use GlpiPlugin\Ticketboost\Assignment\Assigner;
use GlpiPlugin\Ticketboost\Http\JsonResponse;
use Group;
use Session;
use Ticket;
use User;

final class AssignerTest extends DbTestCase
{
    private function ticket(): int
    {
        return $this->createItem(Ticket::class, [
            'name'        => 'Assigner test',
            'content'     => 'Assigner test',
            'entities_id' => 0,
        ])->getID();
    }

    /**
     * @param array<string, mixed> $body
     */
    private function send(array $body): JsonResponse
    {
        return Assigner::respond((string) json_encode($body));
    }

    private function userId(string $login): int
    {
        return getItemByTypeName(User::class, $login, true);
    }

    private function me(): Actor
    {
        return Actor::user((int) Session::getLoginUserID());
    }

    private function ticketStatus(int $ticket): int
    {
        $item = new Ticket();
        $item->getFromDB($ticket);

        return (int) $item->fields['status'];
    }

    private function group(bool $assignable): int
    {
        return $this->createItem(Group::class, [
            'name'         => 'Assigner group',
            'entities_id'  => 0,
            'is_recursive' => 1,
            'is_assign'    => $assignable ? 1 : 0,
        ])->getID();
    }

    public function testAssignMeAssignsTheTicketAndMovesItToAssigned(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();

        $reply = $this->send(['action' => 'assign_me', 'id' => $id]);

        $this->assertTrue($reply->ok);
        $this->assertSame(Ticket::getFormURLWithID($id, false), $reply->redirect);
        $this->assertNotNull(Assignees::link($id, $this->me()));
        $this->assertSame(Ticket::ASSIGNED, $this->ticketStatus($id));
    }

    public function testAssignMeTwiceIsRefused(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();
        $this->send(['action' => 'assign_me', 'id' => $id]);

        $reply = $this->send(['action' => 'assign_me', 'id' => $id]);

        $this->assertFalse($reply->ok);
        $this->assertSame(409, $reply->status);
    }

    public function testUnassignMeRemovesTheLink(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();
        $this->send(['action' => 'assign_me', 'id' => $id]);

        $this->assertTrue($this->send(['action' => 'unassign_me', 'id' => $id])->ok);
        $this->assertNull(Assignees::link($id, $this->me()));

        $again = $this->send(['action' => 'unassign_me', 'id' => $id]);
        $this->assertSame(409, $again->status);
    }

    public function testAssignOtherAssignsAUserWhoCanOwnTickets(): void
    {
        $this->login('glpi', 'glpi');
        $id   = $this->ticket();
        $tech = Actor::user($this->userId('tech'));

        $reply = $this->send(['action' => 'assign_other', 'id' => $id, 'value' => $tech->toString()]);

        $this->assertTrue($reply->ok);
        $this->assertNotNull(Assignees::link($id, $tech));

        $again = $this->send(['action' => 'assign_other', 'id' => $id, 'value' => $tech->toString()]);
        $this->assertSame(409, $again->status);
    }

    public function testAssignOtherRefusesAUserWithoutTheRightToOwnTickets(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();

        $reply = $this->send(['action' => 'assign_other', 'id' => $id, 'value' => 'user:' . $this->userId('post-only')]);

        $this->assertSame(400, $reply->status);
        $this->assertNull(Assignees::link($id, Actor::user($this->userId('post-only'))));
    }

    public function testAssignOtherRefusesAnUnknownUser(): void
    {
        $this->login('glpi', 'glpi');

        $reply = $this->send(['action' => 'assign_other', 'id' => $this->ticket(), 'value' => 'user:999999']);

        $this->assertSame(400, $reply->status);
    }

    public function testAssignOtherAssignsAGroupThatCanBeAssigned(): void
    {
        $this->login('glpi', 'glpi');
        $id    = $this->ticket();
        $group = Actor::group($this->group(true));

        $this->assertTrue($this->send(['action' => 'assign_other', 'id' => $id, 'value' => $group->toString()])->ok);
        $this->assertNotNull(Assignees::link($id, $group));
    }

    public function testAssignOtherRefusesAGroupThatCannotBeAssigned(): void
    {
        $this->login('glpi', 'glpi');
        $group = Actor::group($this->group(false));

        $reply = $this->send(['action' => 'assign_other', 'id' => $this->ticket(), 'value' => $group->toString()]);

        $this->assertSame(400, $reply->status);
    }

    public function testUnassignOthersRemovesTheSelectedAssigneesOnly(): void
    {
        $this->login('glpi', 'glpi');
        $id    = $this->ticket();
        $tech  = Actor::user($this->userId('tech'));
        $group = Actor::group($this->group(true));
        $this->send(['action' => 'assign_me', 'id' => $id]);
        $this->send(['action' => 'assign_other', 'id' => $id, 'value' => $tech->toString()]);
        $this->send(['action' => 'assign_other', 'id' => $id, 'value' => $group->toString()]);

        $reply = $this->send([
            'action' => 'unassign_others',
            'id'     => $id,
            'value'  => [$tech->toString(), $group->toString()],
        ]);

        $this->assertTrue($reply->ok);
        $this->assertNull(Assignees::link($id, $tech));
        $this->assertNull(Assignees::link($id, $group));
        $this->assertNotNull(Assignees::link($id, $this->me()));
    }

    public function testUnassignOthersChangesNothingWhenOneEntryIsNotAssigned(): void
    {
        $this->login('glpi', 'glpi');
        $id   = $this->ticket();
        $tech = Actor::user($this->userId('tech'));
        $this->send(['action' => 'assign_other', 'id' => $id, 'value' => $tech->toString()]);

        $reply = $this->send([
            'action' => 'unassign_others',
            'id'     => $id,
            'value'  => [$tech->toString(), 'group:999999'],
        ]);

        $this->assertSame(409, $reply->status);
        $this->assertNotNull(Assignees::link($id, $tech));
    }

    public function testUnassignOthersDoesNotRemoveTheCurrentUser(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();
        $this->send(['action' => 'assign_me', 'id' => $id]);

        $reply = $this->send(['action' => 'unassign_others', 'id' => $id, 'value' => [$this->me()->toString()]]);

        $this->assertSame(409, $reply->status);
        $this->assertNotNull(Assignees::link($id, $this->me()));
    }

    public function testAnInvalidBodyIsRefused(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertSame(400, Assigner::respond('not json')->status);
        $this->assertSame(400, $this->send(['action' => 'close', 'id' => 1])->status);
    }

    public function testAMissingTicketIsNotFound(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertSame(404, $this->send(['action' => 'assign_me', 'id' => 999999])->status);
    }

    public function testAClosedTicketCannotBeChanged(): void
    {
        global $DB;

        $this->login('glpi', 'glpi');
        $id = $this->ticket();
        $DB->update(Ticket::getTable(), ['status' => Ticket::CLOSED], ['id' => $id]);

        $this->assertSame(409, $this->send(['action' => 'assign_me', 'id' => $id])->status);
    }

    public function testATicketTheUserCannotSeeLooksMissing(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();

        $this->login('post-only', 'postonly');

        $this->assertSame(404, $this->send(['action' => 'assign_me', 'id' => $id])->status);
    }

    public function testSelfServiceCannotAssignItsOwnTicket(): void
    {
        $this->login('post-only', 'postonly');
        $id = $this->createItem(Ticket::class, [
            'name'        => 'Own ticket',
            'content'     => 'Own ticket',
            'entities_id' => 0,
        ])->getID();

        $this->assertSame(403, $this->send(['action' => 'assign_me', 'id' => $id])->status);
        $this->assertSame(403, $this->send(['action' => 'assign_other', 'id' => $id, 'value' => 'user:2'])->status);
    }
}
