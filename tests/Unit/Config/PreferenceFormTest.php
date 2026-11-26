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

use GlpiPlugin\Ticketboost\Config\PluginConfig;
use GlpiPlugin\Ticketboost\Config\PreferenceForm;
use PHPUnit\Framework\TestCase;

final class PreferenceFormTest extends TestCase
{
    public function testAnUntouchedFormOverridesNothing(): void
    {
        $admin = new PluginConfig(keymap: ['next' => ['KeyJ']]);
        $form  = PreferenceForm::fromPost([
            'enabled' => '1',
            'leader'  => ['key' => '', 'mode' => '', 'taps' => '', 'holdMs' => ''],
            'keymap'  => ['next' => 'KeyJ', 'previous' => ''],
        ], $admin);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->enabled);
        $this->assertSame([], $form->leader);
        $this->assertSame([], $form->keymap);
    }

    public function testAnUncheckedSwitchTurnsThePluginOff(): void
    {
        $this->assertFalse(PreferenceForm::fromPost([], new PluginConfig())->enabled);
    }

    public function testLeaderOverridesAreClampedAndUnknownValuesIgnored(): void
    {
        $form = PreferenceForm::fromPost(['leader' => [
            'key'         => 'Alt',
            'mode'        => 'dance',
            'taps'        => '9',
            'tapMaxMs'    => 'soon',
            'holdMs'      => '800',
            'tapWindowMs' => ['x'],
            'evil'        => '1',
        ]], new PluginConfig());

        $this->assertSame(['key' => 'Alt', 'taps' => 3, 'holdMs' => 800], $form->leader);
    }

    public function testShortcutsAreNormalizedAndDisabledActionsStoreAnEmptyList(): void
    {
        $form = PreferenceForm::fromPost([
            'keymap'   => ['next' => 'Shift+Ctrl+KeyK', 'close' => 'KeyZ'],
            'disabled' => ['close' => '1'],
        ], new PluginConfig());

        $this->assertTrue($form->isValid());
        $this->assertEquals(['close' => [], 'next' => ['Ctrl+Shift+KeyK']], $form->keymap);
    }

    public function testDisablingAnActionWithoutShortcutsStoresNothing(): void
    {
        $admin = new PluginConfig(keymap: ['close' => []]);

        $this->assertSame([], PreferenceForm::fromPost(['disabled' => ['close' => '1', 'reboot' => '1']], $admin)->keymap);
    }

    public function testAFreedShortcutCanBeReused(): void
    {
        $form = PreferenceForm::fromPost([
            'keymap'   => ['next' => 'KeyC'],
            'disabled' => ['close' => '1'],
        ], new PluginConfig());

        $this->assertTrue($form->isValid());
    }

    public function testAConflictWithTheAdministratorKeymapIsReportedAndTheTextKept(): void
    {
        $admin = new PluginConfig(keymap: ['next' => ['KeyJ']]);
        $form  = PreferenceForm::fromPost(['keymap' => ['close' => 'KeyJ']], $admin);

        $this->assertFalse($form->isValid());
        $this->assertSame('conflict', $form->issues[0]['code']);
        $this->assertSame(['close' => ['KeyJ']], $form->keymap);
    }

    public function testAnInvalidShortcutIsReported(): void
    {
        $form = PreferenceForm::fromPost(['keymap' => ['next' => 'Hyper+KeyN']], new PluginConfig());

        $this->assertSame(
            [['code' => 'invalid_binding', 'action' => 'next', 'binding' => 'Hyper+KeyN']],
            $form->issues,
        );
        $this->assertSame(['next' => ['Hyper+KeyN']], $form->keymap);
    }
}
