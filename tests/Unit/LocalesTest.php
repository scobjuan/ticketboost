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

use PHPUnit\Framework\TestCase;

/**
 * Every user-facing string in the PHP code must be in the template and translated, so a
 * new `__()` cannot ship untranslated (add-translation playbook).
 */
final class LocalesTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function msgidsInCode(): array
    {
        $root = dirname(__DIR__, 2);
        $ids  = [];
        $files = array_merge(
            glob($root . '/src/*.php') ?: [],
            glob($root . '/src/*/*.php') ?: [],
            glob($root . '/ajax/*.php') ?: [],
            glob($root . '/front/*.php') ?: [],
            [$root . '/setup.php', $root . '/hook.php'],
        );
        foreach ($files as $file) {
            preg_match_all("/__\('((?:[^'\\\\]|\\\\.)*)',\s*'ticketboost'\)/", (string) file_get_contents($file), $matches);
            array_push($ids, ...$matches[1]);
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, string> msgid to msgstr (single-line entries only).
     */
    private function entries(string $file): array
    {
        preg_match_all('/^msgid "(.+)"\nmsgstr "(.*)"$/m', (string) file_get_contents(dirname(__DIR__, 2) . '/locales/' . $file), $m, PREG_SET_ORDER);

        return array_column($m, 2, 1);
    }

    public function testThereAreStringsToCheck(): void
    {
        $this->assertGreaterThan(30, count($this->msgidsInCode()));
    }

    public function testEveryStringIsInTheTemplateAndBothLanguages(): void
    {
        $pot = $this->entries('ticketboost.pot');
        $en  = $this->entries('en_GB.po');
        $es  = $this->entries('es_ES.po');

        foreach ($this->msgidsInCode() as $msgid) {
            $this->assertArrayHasKey($msgid, $pot, "Missing in ticketboost.pot: $msgid");
            $this->assertSame($msgid, $en[$msgid] ?? null, "en_GB must repeat the English text: $msgid");
            $this->assertNotSame('', $es[$msgid] ?? '', "Untranslated in es_ES: $msgid");
        }
    }
}
