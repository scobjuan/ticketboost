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

namespace GlpiPlugin\Ticketboost\Tests\Unit\Config;

use GlpiPlugin\Ticketboost\Config\ConfigForm;
use PHPUnit\Framework\TestCase;

final class ConfigFormTest extends TestCase
{
    public function testAnEmptyFormGivesTheDefaults(): void
    {
        $form = ConfigForm::fromPost([]);

        $this->assertTrue($form->isValid());
        $this->assertSame('Control', $form->config->leader->key);
        $this->assertSame([], $form->config->keymap);
        $this->assertSame('quick_solution', $form->config->closeMode);
    }

    public function testTheSubmittedSettingsAreRead(): void
    {
        $form = ConfigForm::fromPost([
            'leader'     => ['key' => 'Alt', 'mode' => 'hold', 'taps' => '2', 'holdMs' => '5000'],
            'close_mode' => 'direct_close',
            'keymap'     => ['next' => 'Shift+Ctrl+KeyK, ArrowRight', 'close' => '  '],
        ]);

        $this->assertTrue($form->isValid());
        $this->assertSame('Alt', $form->config->leader->key);
        $this->assertSame('hold', $form->config->leader->mode);
        $this->assertSame(2, $form->config->leader->taps);
        $this->assertSame(2000, $form->config->leader->holdMs);
        $this->assertSame('direct_close', $form->config->closeMode);
        $this->assertSame(['next' => ['Ctrl+Shift+KeyK', 'ArrowRight']], $form->config->keymap);
    }

    public function testAnUnknownCloseModeFallsBackToTheQuickSolution(): void
    {
        $this->assertSame('quick_solution', ConfigForm::fromPost(['close_mode' => 'drop_table'])->config->closeMode);
        $this->assertSame('quick_solution', ConfigForm::fromPost(['close_mode' => ['x']])->config->closeMode);
    }

    public function testAFieldEqualToTheDefaultStoresNothing(): void
    {
        $form = ConfigForm::fromPost(['keymap' => ['previous' => 'KeyP, ArrowLeft']]);

        $this->assertTrue($form->isValid());
        $this->assertSame([], $form->config->keymap);
    }

    public function testAnInvalidShortcutIsReportedAndNotStored(): void
    {
        $form = ConfigForm::fromPost(['keymap' => ['next' => 'Hyper+KeyN, KeyK']]);

        $this->assertFalse($form->isValid());
        $this->assertSame(
            [['code' => 'invalid_binding', 'action' => 'next', 'binding' => 'Hyper+KeyN']],
            $form->issues,
        );
        $this->assertSame([], $form->config->keymap);
    }

    public function testAReservedShortcutIsReported(): void
    {
        $form = ConfigForm::fromPost(['keymap' => ['next' => 'Ctrl+KeyW']]);

        $this->assertFalse($form->isValid());
        $this->assertSame('reserved_binding', $form->issues[0]['code']);
    }

    public function testAShortcutUsedTwiceIsReportedForBothActions(): void
    {
        $form = ConfigForm::fromPost(['keymap' => ['next' => 'KeyP']]);

        $this->assertFalse($form->isValid());
        $this->assertSame(['next', 'previous'], array_column($form->issues, 'action'));
        $this->assertSame('conflict', $form->issues[0]['code']);
    }

    public function testAnUnknownActionIsReported(): void
    {
        $form = ConfigForm::fromPost(['keymap' => ['reboot' => 'KeyB']]);

        $this->assertFalse($form->isValid());
        $this->assertSame([['code' => 'unknown_action', 'action' => 'reboot']], $form->issues);
    }

    public function testMalformedFieldsAreIgnored(): void
    {
        $form = ConfigForm::fromPost(['keymap' => ['next' => ['KeyK'], 7 => 'KeyJ'], 'leader' => 'Alt']);

        $this->assertTrue($form->isValid());
        $this->assertSame([], $form->config->keymap);
        $this->assertSame('Control', $form->config->leader->key);
    }

    public function testTheTypedTextIsGivenBackForTheForm(): void
    {
        $this->assertSame(
            ['next' => 'Hyper+KeyN, KeyK'],
            ConfigForm::typed(['keymap' => ['next' => 'Hyper+KeyN KeyK', 'previous' => '']]),
        );
        $this->assertSame([], ConfigForm::typed([]));
    }
}
