# Project memory (max 250 lines, prune every session)

Updated: 2026-11-27 | Version: unreleased (target 0.1.0) | Phase: 7, release pending

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- Phases 0 to 6 done and merged; Phase 7 complete except publishing 0.1.0. Release PR #3 is open, NOT merged.
- Before publishing, the owner asked for everything in PLAN to be in place. Merged: docs, screenshots, logo,
  manifest (#18), logo inside the archive (#20), Stylelint (#21), group assignment e2e (#22). ADR 0023
  accepted; ADR 0024 records the uncached bootstrap and the `src/` layout.
- `main` is protected by a ruleset (pull request, 10 required checks, linear history, squash only).
- Clean install check (2026-11-27, archive built from PR #3 after #21, specs of #22): install on GLPI 11.0.12
  and 12.0.1, logo shown in the plugin list, e2e 196 passed, no plugin lines in the PHP log, uninstall left no
  table, setting or right. Later merges change nothing that ships in the archive.
- Facts the code relies on: two bundles (ADR 0019); use `Rights::has()` (int in GLPI 11, bool in 12).
- Commits carry no `Co-Authored-By` trailers and PR texts no tool footer (RULES.md), even when a tool
  suggests them; commit scopes are limited to the commitlint list.

## In progress (max 10)

- Branch `docs/pre-release`: these notes, ADR 0023 and 0024. Merge it when its checks pass (approved).

## Next steps (max 20)

1. Ask the owner to publish 0.1.0 (not approved yet). Check `gh pr checks 3`, then squash-merge PR #3 (public,
   hard to undo: tags the version, publishes the archive and the docs).
2. Verify the published archive on a clean GLPI 11 and 12 (playbook `release.md`, step 5) and the docs site.
3. Owner: submit `plugin.xml` to the GLPI marketplace; optionally set the repository homepage to the docs.
4. PR #23 (development dependencies, Dependabot's replacement of #19): merge after the release.
5. Ask the owner whether to delete the merged branches on GitHub (`docs/release-ready`, `build/plugin-logo`,
   `ci/stylelint`, `test/group-assignment`, `docs/pre-release`).
6. After 0.1.0, candidates: skip the bootstrap request on the login page (it answers 401); delete the
   preference row when a user is purged; `ActorSearch` scans at most 100 users and the assignee check
   ignores account validity dates; solution templates (ADR 0022).

## Blockers and waiting on owner (max 10)

- Approval to publish 0.1.0.

## Open questions (max 10)

- None. Settled 2026-11-25: Ctrl+N and Ctrl+P held together run the browser action by design (tap Ctrl,
  release, then the letter); the repository stays public; the quick close takes free text only.

## Environment status (max 15)

- Host: Arch Linux (kernel 7.3.4), Node 24, pnpm 12.16.1, podman 6.1.4, podman-compose 1.6.0, msgfmt; no
  PHP, no Docker. Development containers stopped (data kept): `make up`, then `make install-plugin`.
- Throwaway containers `tbrel-glpi11`, `tbrel-glpi12`, `tbrel-db` and network `tbrel` (ports 8111, 8112) were
  left running on 2026-11-27: remove them, with the owner's permission, before the next clean check.
- Images pulled: mariadb:10.11, glpi/glpi:11.0.12 and 12.0.1, githubactions-glpi-apache php-8.2-glpi-11.0.x
  and php-8.3-glpi-12.0.x, githubactions-mariadb:10.11, playwright:v1.65.1-noble, mkdocs-material:9.8.1,
  actionlint:1.7.12.
- Playwright browsers on the host: chromium, firefox (webkit needs `make e2e-container`).
- Agent shells have no SSH agent socket: push over HTTPS with
  `git -c credential.helper= -c credential.helper='!gh auth git-credential' push https://github.com/scobjuan/ticketboost.git <branch>`.
- A local agent link exists (created with `pnpm agents:link`, excluded via `.git/info/exclude`).

## Session log (max 40)

- 2026-11-25 Dependabot PR #10 replaced by #12 and exempted (#13). Phase 6a merged (#14).
- 2026-11-25 Phase 6b implemented, verified on all browsers and merged (#15).
- 2026-11-25 Phase 7: accessibility and body cap merged (#16, #17); owner decided ADR 0021 and 0022.
- 2026-11-25 Release preparation in PR #18, checked on clean GLPI installs. Stopped at: owner ended the
  session before merging. Next: "Next steps" 1.
- 2026-11-27 Merged #18 and #20 to #22, protected `main`, accepted ADR 0023 and 0024, checked the final
  package on clean installs. Stopped at: release not published. Next: "Next steps" 1.

## ADR index

- See `.agents/decisions/README.md`.
