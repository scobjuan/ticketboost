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

namespace GlpiPlugin\Ticketboost;

/**
 * Resolves which built frontend assets exist and must be registered in GLPI.
 *
 * Assets are referenced relative to the plugin `public/` directory, which is
 * the only directory GLPI 11+ serves for a plugin.
 */
final readonly class Assets
{
    public const SCRIPT = 'js/ticketboost.js';

    public const STYLESHEET = 'css/ticketboost.css';

    public function __construct(private string $public_dir) {}

    /**
     * @return list<string> JavaScript modules to register.
     */
    public function scripts(): array
    {
        return $this->existing([self::SCRIPT]);
    }

    /**
     * @return list<string> Stylesheets to register.
     */
    public function stylesheets(): array
    {
        return $this->existing([self::STYLESHEET]);
    }

    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function existing(array $paths): array
    {
        return array_values(array_filter(
            $paths,
            fn(string $path): bool => is_file($this->public_dir . '/' . $path),
        ));
    }
}
