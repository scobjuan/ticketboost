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
use GlpiPlugin\Ticketboost\Navigation\Navigator;
use Session;
use Ticket;
use User;

final class NavigatorTest extends DbTestCase
{
    /**
     * @return list<int> The ids of three new tickets.
     */
    private function tickets(): array
    {
        $ids = [];
        foreach (['one', 'two', 'three'] as $name) {
            $ids[] = $this->createItem(Ticket::class, [
                'name'        => 'Navigator ' . $name,
                'content'     => 'Navigator test',
                'entities_id' => 0,
            ])->getID();
        }

        return $ids;
    }

    /**
     * Three tickets assigned to the current user, with priorities 4, 5 and 3.
     *
     * @return list<int>
     */
    private function assignedTickets(): array
    {
        $ids = [];
        foreach ([4, 5, 3] as $priority) {
            $ids[] = $this->createItem(Ticket::class, [
                'name'             => 'Assigned ' . $priority,
                'content'          => 'Navigator test',
                'entities_id'      => 0,
                'priority'         => $priority,
                '_users_id_assign' => Session::getLoginUserID(),
            ])->getID();
        }

        return $ids;
    }

    /**
     * @return array{0: bool, 1: ?int, 2: ?string, 3: int}
     */
    private function go(string $to, ?int $id = null): array
    {
        $query = ['to' => $to] + ($id === null ? [] : ['id' => (string) $id]);
        $reply = Navigator::respond($query);

        return [$reply->ok, $reply->toArray()['data']['id'] ?? null, $reply->redirect, $reply->status];
    }

    public function testNextAndPreviousFollowTheSearchResultList(): void
    {
        $this->login('glpi', 'glpi');
        [$a, $b, $c] = $this->tickets();
        $_SESSION['glpilistitems'][Ticket::class] = [$a, $b, $c];

        $this->assertSame([true, $c, Ticket::getFormURLWithID($c, false), 200], $this->go('next', $b));
        $this->assertSame([true, $a, Ticket::getFormURLWithID($a, false), 200], $this->go('previous', $b));
    }

    public function testNavigationIsCircular(): void
    {
        $this->login('glpi', 'glpi');
        [$a, $b, $c] = $this->tickets();
        $_SESSION['glpilistitems'][Ticket::class] = [$a, $b, $c];

        $this->assertSame($a, $this->go('next', $c)[1]);
        $this->assertSame($c, $this->go('previous', $a)[1]);
    }

    public function testTheSessionListMayHoldNumericStrings(): void
    {
        $this->login('glpi', 'glpi');
        [$a, $b] = $this->tickets();
        $_SESSION['glpilistitems'][Ticket::class] = [(string) $a, (string) $b, 'junk', 0];

        $this->assertSame($b, $this->go('next', $a)[1]);
    }

    public function testTicketsThatCannotBeOpenedAreSkipped(): void
    {
        $this->login('glpi', 'glpi');
        [$a, $b] = $this->tickets();
        $_SESSION['glpilistitems'][Ticket::class] = [$a, 999999999, $b];

        $this->assertSame($b, $this->go('next', $a)[1]);
    }

    public function testWithoutAResultListTheUnresolvedTicketsAreWalkedByPriority(): void
    {
        $this->login('glpi', 'glpi');
        [$a, $b, $c] = $this->assignedTickets();
        unset($_SESSION['glpilistitems']);

        // Most urgent first: b (5), a (4), c (3).
        $this->assertSame($c, $this->go('next', $a)[1]);
        $this->assertSame($b, $this->go('next', $c)[1]);
        $this->assertSame($a, $this->go('next', $b)[1]);
    }

    public function testTheFallbackLeavesANoticeForThePageThatOpensNext(): void
    {
        $this->login('glpi', 'glpi');
        [$a] = $this->assignedTickets();
        unset($_SESSION['glpilistitems'], $_SESSION['MESSAGE_AFTER_REDIRECT']);

        $this->go('next', $a);

        $this->assertNotEmpty($_SESSION['MESSAGE_AFTER_REDIRECT'][INFO] ?? []);
    }

    public function testNoNoticeWhenTheResultListIsUsed(): void
    {
        $this->login('glpi', 'glpi');
        [$a, $b] = $this->assignedTickets();
        $_SESSION['glpilistitems'][Ticket::class] = [$a, $b];
        unset($_SESSION['MESSAGE_AFTER_REDIRECT']);

        $this->go('next', $a);

        $this->assertEmpty($_SESSION['MESSAGE_AFTER_REDIRECT'] ?? []);
    }

    public function testResolvedTicketsAreNotWalkedInTheFallback(): void
    {
        $this->login('glpi', 'glpi');
        [$a, $b, $c] = $this->assignedTickets();
        $this->updateItem(Ticket::class, $c, ['status' => Ticket::CLOSED]);
        unset($_SESSION['glpilistitems']);

        // Without c the order is b (5), a (4).
        $this->assertSame($b, $this->go('next', $a)[1]);
        $this->assertSame($a, $this->go('next', $b)[1]);
    }

    public function testACurrentTicketOutsideTheListFallsBackToUnresolvedTickets(): void
    {
        $this->login('glpi', 'glpi');
        [$a, $b] = $this->assignedTickets();
        $_SESSION['glpilistitems'][Ticket::class] = [$b];

        $this->assertTrue($this->go('previous', $a)[0]);
    }

    public function testSelfServiceOnlyWalksTheirOwnTickets(): void
    {
        $this->login('glpi', 'glpi');
        $own = $this->createItem(Ticket::class, [
            'name'                => 'Own ticket',
            'content'             => 'Requested by post-only',
            'entities_id'         => 0,
            '_users_id_requester' => getItemByTypeName(User::class, 'post-only', true),
        ])->getID();
        $this->tickets();
        $this->login('post-only', 'postonly');
        unset($_SESSION['glpilistitems']);

        $reply = $this->go('next', $own);

        $this->assertFalse($reply[0]);
        $this->assertSame(404, $reply[3]);
        $this->assertNull($reply[1]);
    }

    public function testThereIsNowhereToGoWithASingleTicket(): void
    {
        $this->login('glpi', 'glpi');
        $a = $this->tickets()[0];
        $_SESSION['glpilistitems'][Ticket::class] = [$a];

        $reply = $this->go('next', $a);

        $this->assertFalse($reply[0]);
        $this->assertSame(404, $reply[3]);
    }

    public function testListGoesToTheTicketSearch(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertSame([true, null, Ticket::getSearchURL(false), 200], $this->go('list'));
    }

    public function testMyTicketsFiltersByTechnicianInTheCentralInterface(): void
    {
        $this->login('tech', 'tech');

        $url = Navigator::myTicketsUrl();

        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
        $this->assertStringStartsWith(Ticket::getSearchURL(false), $url);
        $this->assertSame('5', $params['criteria'][0]['field']);
        $this->assertSame('equals', $params['criteria'][0]['searchtype']);
        $this->assertSame((string) Session::getLoginUserID(), $params['criteria'][0]['value']);
        $this->assertTrue($this->go('my_tickets')[0]);
    }

    public function testMyTicketsFiltersByRequesterForSelfService(): void
    {
        $this->login('post-only', 'postonly');

        parse_str((string) parse_url(Navigator::myTicketsUrl(), PHP_URL_QUERY), $params);

        $this->assertSame('4', $params['criteria'][0]['field']);
    }

    public function testInvalidRequestsAreRefused(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertSame(400, Navigator::respond(['to' => 'delete'])->status);
        $this->assertSame(400, Navigator::respond(['to' => 'next'])->status);
        $this->assertSame(400, Navigator::respond(['to' => 'list', 'x' => '1'])->status);
    }

    public function testWithoutTheTicketRightNothingIsResolved(): void
    {
        $this->login('glpi', 'glpi');
        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = 0;
        $_SESSION['glpiactiveprofile']['ticketvalidation'] = 0;

        $this->assertSame(403, Navigator::respond(['to' => 'list'])->status);
    }
}
