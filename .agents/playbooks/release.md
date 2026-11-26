---
title: Release a version
when: The owner asks to publish a release, or to check why a release did not happen.
---

# Release a version

Releases are automatic; humans and agents only merge.

1. Conventional commits land on `main` through squash-merged PRs.
2. The Release workflow (release-please, owner token `RELEASE_PLEASE_TOKEN`) opens or
   updates a release PR `chore(main): release X.Y.Z` with the CHANGELOG and the version in
   `setup.php` and `plugin.xml`.
3. With owner approval, merge the release PR. The workflow tags `vX.Y.Z`, creates the
   GitHub Release, builds `ticketboost-X.Y.Z.tar.bz2` and attaches it, then deploys docs.
4. First release only: the version is forced with a commit footer `Release-As: 0.1.0`.
5. Verify the archive on a clean GLPI 11 and 12, before merging the release PR (build it from the
   release branch with `make release-dry`) and again with the published asset:
   - throwaway containers on their own podman network: `mariadb:10.11` with `docker/db/init.sql`, and
     `glpi/glpi:<version>` with the `GLPI_DB_*` variables of `compose.yaml`, without mounting the
     repository, on free ports; the development environment stays untouched;
   - `podman cp` the archive, extract it as root into `/var/www/glpi/plugins`, then as `www-data` run
     `bin/console plugin:install --username=glpi ticketboost` and `plugin:activate ticketboost`;
   - e2e: copy `e2e/seed/seed.php` to each container, run it as `www-data`, and run Playwright with
     `GLPI11_URL` and `GLPI12_URL` pointing at them (for WebKit pass both to the Playwright container);
   - check `/var/glpi/logs/php-errors.log`, run `plugin:uninstall` and confirm that no plugin table,
     `plugin:ticketboost` setting or `plugin_ticketboost` right remains; remove the containers.
6. Marketplace: the owner submits or updates the plugin on the GLPI marketplace using
   `plugin.xml` (raw URL on `main`). Its logo and screenshots live in `docs/assets/`; the
   download address points to `ticketboost.tar.bz2`, the fixed-name copy of the archive.
   Never create tags or releases by hand.
