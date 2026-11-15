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

use GlpiPlugin\Ticketboost\Navigation\ListWalker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ListWalkerTest extends TestCase
{
    /**
     * @return iterable<string, array{list<int>, int, int, ?int}>
     */
    public static function walkProvider(): iterable
    {
        yield 'next in the middle' => [[10, 20, 30], 20, 1, 30];
        yield 'previous in the middle' => [[10, 20, 30], 20, -1, 10];
        yield 'next wraps to the first' => [[10, 20, 30], 30, 1, 10];
        yield 'previous wraps to the last' => [[10, 20, 30], 10, -1, 30];
        yield 'unknown current: next is the first' => [[10, 20, 30], 99, 1, 10];
        yield 'unknown current: previous is the last' => [[10, 20, 30], 99, -1, 30];
        yield 'empty list' => [[], 5, 1, null];
        yield 'only the current ticket' => [[5], 5, 1, null];
        yield 'invalid step' => [[10, 20], 10, 2, null];
    }

    /**
     * @param list<int> $ids
     */
    #[DataProvider('walkProvider')]
    public function testStep(array $ids, int $current, int $step, ?int $expected): void
    {
        $this->assertSame($expected, ListWalker::step($ids, $current, $step, static fn(int $id): bool => true));
    }

    public function testNotAcceptableIdsAreSkipped(): void
    {
        $accept = static fn(int $id): bool => $id !== 20 && $id !== 30;

        $this->assertSame(10, ListWalker::step([10, 20, 30, 40], 40, 1, $accept));
        $this->assertSame(40, ListWalker::step([10, 20, 30, 40], 10, -1, $accept));
        $this->assertSame(40, ListWalker::step([10, 20, 30, 40], 10, 1, $accept));
    }

    public function testNothingAcceptableMeansNoTarget(): void
    {
        $this->assertNull(ListWalker::step([10, 20, 30], 20, 1, static fn(int $id): bool => false));
    }
}
