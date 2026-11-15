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

namespace GlpiPlugin\Ticketboost\Tests\Unit\Navigation;

use GlpiPlugin\Ticketboost\Navigation\NavigationRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NavigationRequestTest extends TestCase
{
    /**
     * @return iterable<string, array{array<mixed>, ?array{string, int}}>
     */
    public static function queryProvider(): iterable
    {
        yield 'next with an id' => [['to' => 'next', 'id' => '12'], ['next', 12]];
        yield 'previous with an id' => [['to' => 'previous', 'id' => '7'], ['previous', 7]];
        yield 'list without an id' => [['to' => 'list'], ['list', 0]];
        yield 'my tickets without an id' => [['to' => 'my_tickets'], ['my_tickets', 0]];
        yield 'list with an id' => [['to' => 'list', 'id' => '3'], ['list', 3]];
        yield 'next needs an id' => [['to' => 'next'], null];
        yield 'unknown target' => [['to' => 'delete', 'id' => '1'], null];
        yield 'missing target' => [['id' => '1'], null];
        yield 'target is not a string' => [['to' => ['next'], 'id' => '1'], null];
        yield 'unknown parameter' => [['to' => 'list', 'extra' => '1'], null];
        yield 'zero id' => [['to' => 'next', 'id' => '0'], null];
        yield 'negative id' => [['to' => 'next', 'id' => '-4'], null];
        yield 'text id' => [['to' => 'next', 'id' => '4abc'], null];
        yield 'id with leading zero' => [['to' => 'next', 'id' => '04'], null];
        yield 'id too long' => [['to' => 'next', 'id' => '12345678901'], null];
        yield 'id is not a string' => [['to' => 'next', 'id' => ['1']], null];
    }

    /**
     * @param array<mixed> $query
     * @param array{string, int}|null $expected
     */
    #[DataProvider('queryProvider')]
    public function testFromQuery(array $query, ?array $expected): void
    {
        $request = NavigationRequest::fromQuery($query);

        if ($expected === null) {
            $this->assertNull($request);

            return;
        }

        $this->assertNotNull($request);
        $this->assertSame($expected, [$request->to, $request->id]);
    }
}
