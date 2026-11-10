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
5. Verify: the release page has the archive; install it on a clean GLPI 11 and 12
   (`make reset up`, extract into the container plugins dir, activate).
6. Marketplace: the owner submits or updates the plugin on the GLPI marketplace using
   `plugin.xml` (raw URL on `main`).
   Never create tags or releases by hand.
