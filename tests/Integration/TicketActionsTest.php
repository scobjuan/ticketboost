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
use GlpiPlugin\Ticketboost\Action\ActionRouter;
use GlpiPlugin\Ticketboost\Bootstrap\Options;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Http\JsonResponse;
use ITILSolution;
use Ticket;

final class TicketActionsTest extends DbTestCase
{
    private function ticket(): int
    {
        return $this->createItem(Ticket::class, [
            'name'        => 'Ticket actions test',
            'content'     => 'Ticket actions test',
            'entities_id' => 0,
        ])->getID();
    }

    /**
     * @param array<string, mixed> $body
     */
    private function send(array $body): JsonResponse
    {
        return ActionRouter::respond((string) json_encode($body));
    }

    private function field(int $ticket, string $field): int
    {
        $item = new Ticket();
        $item->getFromDB($ticket);

        return (int) $item->fields[$field];
    }

    private function closeMode(string $mode): void
    {
        (new PluginConfig(closeMode: $mode))->save();
    }

    public function testChangeStatusSetsTheStatus(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();

        $reply = $this->send(['action' => 'change_status', 'id' => $id, 'value' => (string) Ticket::WAITING]);

        $this->assertTrue($reply->ok);
        $this->assertSame(Ticket::getFormURLWithID($id, false), $reply->redirect);
        $this->assertSame(Ticket::WAITING, $this->field($id, 'status'));
    }

    public function testChangeStatusToTheCurrentStatusIsRefused(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();

        $reply = $this->send(['action' => 'change_status', 'id' => $id, 'value' => (string) Ticket::INCOMING]);

        $this->assertSame(409, $reply->status);
    }

    public function testChangeStatusRefusesAStatusTheProfileMatrixForbids(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();
        $_SESSION['glpiactiveprofile']['helpdesk_status'][Ticket::INCOMING][Ticket::PLANNED] = 0;
        $_SESSION['glpiactiveprofile']['ticket_status'][Ticket::INCOMING][Ticket::PLANNED]   = 0;

        $reply = $this->send(['action' => 'change_status', 'id' => $id, 'value' => (string) Ticket::PLANNED]);

        $this->assertSame(400, $reply->status);
        $this->assertSame(Ticket::INCOMING, $this->field($id, 'status'));
    }

    public function testChangePriority(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();

        $this->assertTrue($this->send(['action' => 'change_priority', 'id' => $id, 'value' => '5'])->ok);
        $this->assertSame(5, $this->field($id, 'priority'));
        $this->assertSame(409, $this->send(['action' => 'change_priority', 'id' => $id, 'value' => '5'])->status);
    }

    public function testChangePriorityNeedsTheRightToChangeIt(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();
        $_SESSION['glpiactiveprofile'][Ticket::$rightname] &= ~Ticket::CHANGEPRIORITY;

        $reply = $this->send(['action' => 'change_priority', 'id' => $id, 'value' => '5']);

        $this->assertSame(403, $reply->status);
    }

    public function testQuickSolutionAddsASolutionWithTheTextEscaped(): void
    {
        $this->login('glpi', 'glpi');
        $this->closeMode(PluginConfig::CLOSE_QUICK_SOLUTION);
        $id = $this->ticket();

        $reply = $this->send(['action' => 'close', 'id' => $id, 'value' => "Fixed <b>it</b> & done\nsecond line"]);

        $this->assertTrue($reply->ok);
        $this->assertContains($this->field($id, 'status'), [Ticket::SOLVED, Ticket::CLOSED]);
        $solution = new ITILSolution();
        $this->assertTrue($solution->getFromDBByCrit(['itemtype' => Ticket::class, 'items_id' => $id]));
        $this->assertStringContainsString('Fixed &lt;b&gt;it&lt;/b&gt; &amp; done', (string) $solution->fields['content']);
        $this->assertStringNotContainsString('<b>', (string) $solution->fields['content']);
    }

    public function testQuickSolutionNeedsText(): void
    {
        $this->login('glpi', 'glpi');
        $this->closeMode(PluginConfig::CLOSE_QUICK_SOLUTION);
        $id = $this->ticket();

        $reply = $this->send(['action' => 'close', 'id' => $id]);

        $this->assertSame(400, $reply->status);
        $this->assertSame(Ticket::INCOMING, $this->field($id, 'status'));
    }

    public function testDirectCloseClosesTheTicket(): void
    {
        $this->login('glpi', 'glpi');
        $this->closeMode(PluginConfig::CLOSE_DIRECT);
        $id = $this->ticket();

        $reply = $this->send(['action' => 'close', 'id' => $id]);

        $this->assertTrue($reply->ok);
        $this->assertSame(Ticket::CLOSED, $this->field($id, 'status'));
    }

    public function testDirectCloseDoesNotTakeText(): void
    {
        $this->login('glpi', 'glpi');
        $this->closeMode(PluginConfig::CLOSE_DIRECT);
        $id = $this->ticket();

        $this->assertSame(400, $this->send(['action' => 'close', 'id' => $id, 'value' => 'text'])->status);
        $this->assertSame(Ticket::INCOMING, $this->field($id, 'status'));
    }

    public function testAClosedTicketCannotBeChanged(): void
    {
        $this->login('glpi', 'glpi');
        $this->closeMode(PluginConfig::CLOSE_DIRECT);
        $id = $this->ticket();
        $this->send(['action' => 'close', 'id' => $id]);

        $this->assertSame(409, $this->send(['action' => 'change_priority', 'id' => $id, 'value' => '2'])->status);
    }

    public function testAMissingTicketIsNotFound(): void
    {
        $this->login('glpi', 'glpi');

        $this->assertSame(404, $this->send(['action' => 'change_priority', 'id' => 999999, 'value' => '2'])->status);
    }

    public function testSelfServiceCannotChangeItsOwnTicket(): void
    {
        $this->login('post-only', 'postonly');
        $id = $this->createItem(Ticket::class, ['name' => 'Own', 'content' => 'Own', 'entities_id' => 0])->getID();

        $this->assertSame(403, $this->send(['action' => 'change_priority', 'id' => $id, 'value' => '2'])->status);
        $this->assertSame(403, $this->send(['action' => 'change_status', 'id' => $id, 'value' => '4'])->status);
    }

    public function testTheRouterRoutesAssignmentActionsAndRefusesTheRest(): void
    {
        $this->login('glpi', 'glpi');
        $id = $this->ticket();

        $this->assertTrue($this->send(['action' => 'assign_me', 'id' => $id])->ok);
        $this->assertSame(400, $this->send(['action' => 'delete', 'id' => $id])->status);
        $this->assertSame(400, ActionRouter::respond('nope')->status);
        $this->assertSame(400, ActionRouter::respond('[1]')->status);
    }

    public function testOptionsListStatusesAndPriorities(): void
    {
        $this->login('glpi', 'glpi');

        $options = Options::all();

        $this->assertSame(['1', '2', '3', '4'], array_column($options['change_status'], 'value'));
        $this->assertSame(['1', '2', '3', '4', '5', '6'], array_column($options['change_priority'], 'value'));
        $this->assertSame(Ticket::getStatus(Ticket::WAITING), $options['change_status'][3]['label']);
    }
}
