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

namespace GlpiPlugin\Ticketboost\Tests\Unit\Ticketing;

use GlpiPlugin\Ticketboost\Ticketing\TicketActionRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TicketActionRequestTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ?array{string, int, int, string}}>
     */
    public static function bodyProvider(): iterable
    {
        yield 'status' => ['{"action":"change_status","id":5,"value":"3"}', ['change_status', 5, 3, '']];
        yield 'priority' => ['{"action":"change_priority","id":5,"value":"6"}', ['change_priority', 5, 6, '']];
        yield 'close without text' => ['{"action":"close","id":5}', ['close', 5, 0, '']];
        yield 'close with text' => ['{"action":"close","id":5,"value":"  Done  "}', ['close', 5, 0, 'Done']];
        yield 'not json' => ['nope', null];
        yield 'not an object' => ['"close"', null];
        yield 'unknown action' => ['{"action":"assign_me","id":5}', null];
        yield 'unknown parameter' => ['{"action":"close","id":5,"extra":1}', null];
        yield 'id as text' => ['{"action":"close","id":"5"}', null];
        yield 'zero id' => ['{"action":"close","id":0}', null];
        yield 'status without a value' => ['{"action":"change_status","id":5}', null];
        yield 'status as a number' => ['{"action":"change_status","id":5,"value":3}', null];
        yield 'status solved is not allowed' => ['{"action":"change_status","id":5,"value":"5"}', null];
        yield 'status closed is not allowed' => ['{"action":"change_status","id":5,"value":"6"}', null];
        yield 'status zero' => ['{"action":"change_status","id":5,"value":"0"}', null];
        yield 'status with a newline' => ["{\"action\":\"change_status\",\"id\":5,\"value\":\"2\\n\"}", null];
        yield 'priority zero' => ['{"action":"change_priority","id":5,"value":"0"}', null];
        yield 'priority seven' => ['{"action":"change_priority","id":5,"value":"7"}', null];
        yield 'solution as a list' => ['{"action":"close","id":5,"value":["x"]}', null];
    }

    /**
     * @param array{string, int, int, string}|null $expected
     */
    #[DataProvider('bodyProvider')]
    public function testFromJson(string $raw, ?array $expected): void
    {
        $request = TicketActionRequest::fromJson($raw);

        if ($expected === null) {
            $this->assertNull($request);

            return;
        }

        $this->assertNotNull($request);
        $this->assertSame($expected, [$request->action, $request->id, $request->number, $request->text]);
    }

    public function testASolutionThatIsTooLongIsRefused(): void
    {
        $raw = (string) json_encode(['action' => 'close', 'id' => 5, 'value' => str_repeat('a', TicketActionRequest::MAX_SOLUTION_LENGTH + 1)]);

        $this->assertNull(TicketActionRequest::fromJson($raw));
    }
}
