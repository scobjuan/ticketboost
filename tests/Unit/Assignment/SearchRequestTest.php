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

use GlpiPlugin\Ticketboost\Assignment\SearchRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SearchRequestTest extends TestCase
{
    /**
     * @return iterable<string, array{array<mixed>, ?array{string, int, string}}>
     */
    public static function queryProvider(): iterable
    {
        yield 'assign other' => [['action' => 'assign_other', 'id' => '4', 'q' => 'ana'], ['assign_other', 4, 'ana']];
        yield 'unassign others' => [['action' => 'unassign_others', 'id' => '4'], ['unassign_others', 4, '']];
        yield 'the text is trimmed' => [['action' => 'assign_other', 'id' => '4', 'q' => '  ana '], ['assign_other', 4, 'ana']];
        yield 'unknown action' => [['action' => 'assign_me', 'id' => '4'], null];
        yield 'missing action' => [['id' => '4'], null];
        yield 'missing id' => [['action' => 'assign_other'], null];
        yield 'zero id' => [['action' => 'assign_other', 'id' => '0'], null];
        yield 'text id' => [['action' => 'assign_other', 'id' => '4x'], null];
        yield 'long id' => [['action' => 'assign_other', 'id' => '12345678901'], null];
        yield 'unknown parameter' => [['action' => 'assign_other', 'id' => '4', 'x' => '1'], null];
        yield 'text is not a string' => [['action' => 'assign_other', 'id' => '4', 'q' => ['a']], null];
        yield 'text too long' => [['action' => 'assign_other', 'id' => '4', 'q' => str_repeat('a', 101)], null];
    }

    /**
     * @param array<mixed> $query
     * @param array{string, int, string}|null $expected
     */
    #[DataProvider('queryProvider')]
    public function testFromQuery(array $query, ?array $expected): void
    {
        $request = SearchRequest::fromQuery($query);

        if ($expected === null) {
            $this->assertNull($request);

            return;
        }

        $this->assertNotNull($request);
        $this->assertSame($expected, [$request->action, $request->id, $request->query]);
    }
}
