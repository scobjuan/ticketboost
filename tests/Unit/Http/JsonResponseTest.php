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

use GlpiPlugin\Ticketboost\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

final class JsonResponseTest extends TestCase
{
    public function testSuccessEnvelope(): void
    {
        $response = JsonResponse::success(['a' => 1], 'Done', '/front/ticket.form.php?id=2');

        $this->assertSame(
            ['ok' => true, 'message' => 'Done', 'data' => ['a' => 1], 'redirect' => '/front/ticket.form.php?id=2'],
            $response->toArray(),
        );
        $this->assertSame(200, $response->status);
    }

    public function testRedirectIsOmittedWhenAbsent(): void
    {
        $this->assertSame(['ok' => true, 'message' => '', 'data' => null], JsonResponse::success()->toArray());
    }

    public function testErrorEnvelope(): void
    {
        $response = JsonResponse::error('Nope', 403);

        $this->assertSame(['ok' => false, 'message' => 'Nope', 'data' => null], $response->toArray());
        $this->assertSame(403, $response->status);
        $this->assertSame(400, JsonResponse::error('Bad')->status);
    }

    public function testJsonKeepsUnicodeAndSlashes(): void
    {
        $this->assertSame(
            '{"ok":true,"message":"Cerrar sesión","data":"a/b"}',
            str_replace('ó', 'ó', JsonResponse::success('a/b', 'Cerrar sesión')->toJson()),
        );
    }

    public function testSendOutputsTheJsonBody(): void
    {
        ob_start();
        JsonResponse::success(['x' => true])->send();
        $out = (string) ob_get_clean();

        $this->assertSame('{"ok":true,"message":"","data":{"x":true}}', $out);
    }
}
