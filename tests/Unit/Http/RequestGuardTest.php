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

namespace GlpiPlugin\Ticketboost\Tests\Unit\Http;

use GlpiPlugin\Ticketboost\Http\RequestGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestGuardTest extends TestCase
{
    /**
     * @return iterable<string, array{?int, string, bool, string, bool, bool, ?int}>
     */
    public static function requestProvider(): iterable
    {
        $use       = RequestGuard::LEVEL_USE;
        $configure = RequestGuard::LEVEL_CONFIGURE;
        $auth      = RequestGuard::LEVEL_AUTHENTICATED;

        yield 'anonymous' => [null, 'GET', true, $auth, true, true, 401];
        yield 'user id zero' => [0, 'GET', true, $auth, true, true, 401];
        yield 'wrong method' => [2, 'POST', true, $use, true, true, 405];
        yield 'not ajax' => [2, 'GET', false, $use, true, true, 400];
        yield 'authenticated level needs no right' => [2, 'GET', true, $auth, false, false, null];
        yield 'use level without right' => [2, 'GET', true, $use, false, false, 403];
        yield 'use level with right' => [2, 'GET', true, $use, true, false, null];
        yield 'configure level with use only' => [2, 'GET', true, $configure, true, false, 403];
        yield 'configure level with right' => [2, 'GET', true, $configure, true, true, null];
        yield 'unknown level is denied' => [2, 'GET', true, 'root', true, true, 403];
        yield 'method is case-insensitive' => [2, 'get', true, $use, true, false, null];
    }

    #[DataProvider('requestProvider')]
    public function testEvaluate(
        ?int $user_id,
        string $method,
        bool $ajax,
        string $level,
        bool $can_use,
        bool $can_configure,
        ?int $expected_status,
    ): void {
        $response = RequestGuard::evaluate('GET', $method, $user_id, $ajax, $level, $can_use, $can_configure);

        if ($expected_status === null) {
            $this->assertNull($response);

            return;
        }

        $this->assertNotNull($response);
        $this->assertFalse($response->ok);
        $this->assertSame($expected_status, $response->status);
        $this->assertNotSame('', $response->message);
    }

    public function testChecksRunInTheDocumentedOrder(): void
    {
        $anonymous_wrong_method = RequestGuard::evaluate('POST', 'GET', null, false, RequestGuard::LEVEL_USE, false, false);
        $wrong_method_not_ajax  = RequestGuard::evaluate('POST', 'GET', 2, false, RequestGuard::LEVEL_USE, false, false);
        $not_ajax_no_right      = RequestGuard::evaluate('GET', 'GET', 2, false, RequestGuard::LEVEL_USE, false, false);

        $this->assertSame(401, $anonymous_wrong_method?->status);
        $this->assertSame(405, $wrong_method_not_ajax?->status);
        $this->assertSame(400, $not_ajax_no_right?->status);
    }
}
