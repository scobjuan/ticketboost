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

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Ticketboost\Config\ConfigForm;
use GlpiPlugin\Ticketboost\Config\ConfigPage;
use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Rights;

// GLPI refuses a POST that fails its CSRF check before this file runs (ADR 0018).
Session::checkRight(Rights::NAME, UPDATE);

$config = PluginConfig::load();
$typed  = [];
$issues = [];

if (isset($_POST['update'])) {
    $form = ConfigForm::fromPost($_POST);
    if ($form->isValid()) {
        $form->config->save();
        Session::addMessageAfterRedirect(__('Item successfully updated'));
        Html::back();
    }

    $config = $form->config;
    $typed  = ConfigForm::typed($_POST);
    $issues = $form->issues;
}

Html::header(__('TicketBoost', 'ticketboost'), $_SERVER['PHP_SELF'], 'config', 'plugins');
TemplateRenderer::getInstance()->display('@ticketboost/config.html.twig', [
    'action'   => Html::getPrefixedUrl('/plugins/ticketboost/front/config.form.php'),
    'form_end' => Html::closeForm(false),
] + ConfigPage::params($config, $typed, $issues));
Html::footer();
