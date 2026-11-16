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

use GlpiPlugin\Ticketboost\Assignment\AssignmentRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssignmentRequestTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ?array{string, int, list<string>}}>
     */
    public static function bodyProvider(): iterable
    {
        yield 'assign me' => ['{"action":"assign_me","id":5}', ['assign_me', 5, []]];
        yield 'unassign me' => ['{"action":"unassign_me","id":5}', ['unassign_me', 5, []]];
        yield 'assign other' => ['{"action":"assign_other","id":5,"value":"user:2"}', ['assign_other', 5, ['user:2']]];
        yield 'assign a group' => ['{"action":"assign_other","id":5,"value":"group:9"}', ['assign_other', 5, ['group:9']]];
        yield 'unassign several' => [
            '{"action":"unassign_others","id":5,"value":["user:2","group:9","user:2"]}',
            ['unassign_others', 5, ['user:2', 'group:9']],
        ];
        yield 'not json' => ['nope', null];
        yield 'empty body' => ['', null];
        yield 'not an object' => ['"assign_me"', null];
        yield 'unknown action' => ['{"action":"close","id":5}', null];
        yield 'missing action' => ['{"id":5}', null];
        yield 'missing id' => ['{"action":"assign_me"}', null];
        yield 'id as text' => ['{"action":"assign_me","id":"5"}', null];
        yield 'zero id' => ['{"action":"assign_me","id":0}', null];
        yield 'negative id' => ['{"action":"assign_me","id":-2}', null];
        yield 'unknown parameter' => ['{"action":"assign_me","id":5,"extra":1}', null];
        yield 'assign me with a value' => ['{"action":"assign_me","id":5,"value":"user:2"}', null];
        yield 'unassign me with a value' => ['{"action":"unassign_me","id":5,"value":"user:2"}', null];
        yield 'assign other without a value' => ['{"action":"assign_other","id":5}', null];
        yield 'assign other with a bad value' => ['{"action":"assign_other","id":5,"value":"admin"}', null];
        yield 'assign other with a list' => ['{"action":"assign_other","id":5,"value":["user:2"]}', null];
        yield 'unassign others without a value' => ['{"action":"unassign_others","id":5}', null];
        yield 'unassign others with a text' => ['{"action":"unassign_others","id":5,"value":"user:2"}', null];
        yield 'unassign others with an empty list' => ['{"action":"unassign_others","id":5,"value":[]}', null];
        yield 'unassign others with a bad entry' => ['{"action":"unassign_others","id":5,"value":["user:2","x"]}', null];
    }

    /**
     * @param array{string, int, list<string>}|null $expected
     */
    #[DataProvider('bodyProvider')]
    public function testFromJson(string $raw, ?array $expected): void
    {
        $request = AssignmentRequest::fromJson($raw);

        if ($expected === null) {
            $this->assertNull($request);

            return;
        }

        $this->assertNotNull($request);
        $this->assertSame(
            $expected,
            [$request->action, $request->id, array_map(static fn($actor): string => $actor->toString(), $request->actors)],
        );
    }

    public function testTooManyAssigneesAreRejected(): void
    {
        $values = array_map(static fn(int $id): string => 'user:' . $id, range(1, AssignmentRequest::MAX_ACTORS + 1));
        $raw    = json_encode(['action' => 'unassign_others', 'id' => 5, 'value' => $values]);

        $this->assertNull(AssignmentRequest::fromJson((string) $raw));
    }
}
