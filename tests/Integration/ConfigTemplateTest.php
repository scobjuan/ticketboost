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

namespace GlpiPlugin\Ticketboost\Tests\Integration;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Ticketboost\Config\ConfigPage;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use Html;

final class ConfigTemplateTest extends DbTestCase
{
    /**
     * `DbTestCase` fails on any logged entry, so this also proves that the page raises no
     * deprecation on either GLPI version.
     */
    public function testTheAdministrationPageRendersAndClosesItsForm(): void
    {
        $this->login('glpi', 'glpi');

        $html = TemplateRenderer::getInstance()->render('@ticketboost/config.html.twig', [
            'action'   => '/plugins/ticketboost/front/config.form.php',
            'form_end' => Html::closeForm(false),
        ] + ConfigPage::params(new PluginConfig()));

        $this->assertStringContainsString('name="keymap[next]"', $html);
        $this->assertStringContainsString('</form>', $html);
    }
}
