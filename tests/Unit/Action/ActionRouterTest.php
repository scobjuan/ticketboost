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

namespace GlpiPlugin\Ticketboost\Tests\Unit\Action;

use GlpiPlugin\Ticketboost\Action\ActionRouter;
use GlpiPlugin\Ticketboost\Ticketing\TicketActionRequest;
use PHPUnit\Framework\TestCase;

use function Safe\json_encode;

final class ActionRouterTest extends TestCase
{
    public function testTheLongestAcceptedSolutionFitsInTheBodyThatIsRead(): void
    {
        // A control character is the heaviest one in JSON: six bytes.
        foreach (["\x01", "\u{00F3}", "\u{20AC}"] as $character) {
            $body = json_encode([
                'action' => 'close',
                'id'     => 2147483647,
                'value'  => str_repeat($character, TicketActionRequest::MAX_SOLUTION_LENGTH),
            ]);

            $this->assertLessThanOrEqual(ActionRouter::MAX_BODY_BYTES, strlen($body));
            $this->assertInstanceOf(TicketActionRequest::class, TicketActionRequest::fromJson($body));
        }
    }
}
