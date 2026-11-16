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

namespace GlpiPlugin\Ticketboost\Tests\Unit\Assignment;

use GlpiPlugin\Ticketboost\Assignment\Actor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ActorTest extends TestCase
{
    public function testUsersAndGroupsRoundTripThroughText(): void
    {
        $this->assertSame('user:12', Actor::user(12)->toString());
        $this->assertSame('group:3', Actor::group(3)->toString());

        $actor = Actor::fromString('group:3');
        $this->assertNotNull($actor);
        $this->assertSame([Actor::GROUP, 3], [$actor->kind, $actor->id]);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'unknown kind' => ['ticket:1'];
        yield 'no id' => ['user:'];
        yield 'zero' => ['user:0'];
        yield 'leading zero' => ['user:01'];
        yield 'negative' => ['user:-1'];
        yield 'too long' => ['user:12345678901'];
        yield 'text id' => ['user:1a'];
        yield 'trailing newline' => ["user:1\n"];
        yield 'not a string' => [5];
        yield 'null' => [null];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidTextIsRejected(mixed $value): void
    {
        $this->assertNull(Actor::fromString($value));
    }
}
