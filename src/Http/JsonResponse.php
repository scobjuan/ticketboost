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

namespace GlpiPlugin\Ticketboost\Http;

use function Safe\json_encode;

/**
 * The JSON envelope of every plugin endpoint: `{ok, message, data, redirect?}`.
 */
final readonly class JsonResponse
{
    public function __construct(
        public bool $ok,
        public string $message = '',
        public mixed $data = null,
        public ?string $redirect = null,
        public int $status = 200,
    ) {}

    public static function success(mixed $data = null, string $message = '', ?string $redirect = null): self
    {
        return new self(true, $message, $data, $redirect);
    }

    public static function error(string $message, int $status = 400, mixed $data = null): self
    {
        return new self(false, $message, $data, null, $status);
    }

    /**
     * @return array{ok: bool, message: string, data: mixed, redirect?: string}
     */
    public function toArray(): array
    {
        $body = ['ok' => $this->ok, 'message' => $this->message, 'data' => $this->data];
        if ($this->redirect !== null) {
            $body['redirect'] = $this->redirect;
        }

        return $body;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Sends the response. Never cached: it depends on the session and the rights.
     */
    public function send(): void
    {
        header('Content-Type: application/json; charset=UTF-8', true, $this->status);
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo $this->toJson();
    }
}
