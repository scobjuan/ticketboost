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

namespace GlpiPlugin\Ticketboost\Tests\Unit;

use GlpiPlugin\Ticketboost\Assets;
use PHPUnit\Framework\TestCase;

final class AssetsTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/ticketboost-assets-' . uniqid();
        mkdir($this->dir . '/js', 0o777, true);
        mkdir($this->dir . '/css', 0o777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->dir . '/' . Assets::SCRIPT);
        @unlink($this->dir . '/' . Assets::STYLESHEET);
        @rmdir($this->dir . '/js');
        @rmdir($this->dir . '/css');
        @rmdir($this->dir);
    }

    public function testReturnsNothingWhenAssetsAreNotBuilt(): void
    {
        $assets = new Assets($this->dir);

        $this->assertSame([], $assets->scripts());
        $this->assertSame([], $assets->stylesheets());
    }

    public function testReturnsBuiltAssetsRelativeToPublicDir(): void
    {
        touch($this->dir . '/' . Assets::SCRIPT);
        touch($this->dir . '/' . Assets::STYLESHEET);

        $assets = new Assets($this->dir);

        $this->assertSame(['js/ticketboost.js'], $assets->scripts());
        $this->assertSame(['css/ticketboost.css'], $assets->stylesheets());
    }
}
