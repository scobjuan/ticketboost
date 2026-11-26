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

use function Safe\file_get_contents;
use function Safe\preg_match_all;

/**
 * The marketplace reads `plugin.xml` from the repository: what it points to must exist.
 */
final class PluginManifestTest extends TestCase
{
    private const RAW = 'https://raw.githubusercontent.com/scobjuan/ticketboost/main/';

    private function manifest(): string
    {
        return file_get_contents(__DIR__ . '/../../plugin.xml');
    }

    public function testTheLogoAndTheScreenshotsAreInTheRepository(): void
    {
        preg_match_all('#<(?:logo|screenshot)>([^<]+)</#', $this->manifest(), $matches);
        $addresses = $matches[1] ?? [];

        $this->assertGreaterThan(1, count($addresses));
        foreach ($addresses as $address) {
            $this->assertStringStartsWith(self::RAW, $address);
            $this->assertFileExists(__DIR__ . '/../../' . substr($address, strlen(self::RAW)));
        }
    }

    public function testEveryDownloadAddressFollowsItsVersionAndTheReleaseTooling(): void
    {
        $manifest = $this->manifest();
        preg_match_all('#<num>([^<]+)</num><!-- x-release-please-version -->#', $manifest, $versions);
        preg_match_all('#<download_url>([^<]+)</download_url><!-- x-release-please-version -->#', $manifest, $downloads);

        $this->assertNotEmpty($versions[1] ?? []);
        $this->assertSame(
            array_map(
                static fn(string $version): string => sprintf('https://github.com/scobjuan/ticketboost/releases/download/v%s/ticketboost.tar.bz2', $version),
                $versions[1] ?? [],
            ),
            $downloads[1] ?? [],
        );
    }

    public function testTheReleaseUploadsTheArchiveTheAddressNames(): void
    {
        $this->assertStringContainsString('dist/ticketboost.tar.bz2', file_get_contents(__DIR__ . '/../../scripts/build-release.sh'));
        $this->assertStringContainsString('dist/ticketboost.tar.bz2', file_get_contents(__DIR__ . '/../../.github/workflows/release.yml'));
    }
}
