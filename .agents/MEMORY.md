# Project memory (max 250 lines, prune every session)

Updated: 2026-11-25 | Version: unreleased (target 0.1.0) | Phase: 7, release pending

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- Phases done and merged: 0 to 5 (PRs #4 to #11), 6a admin page (#14), 6b personal settings (#15);
  dev dependencies (#12) and the Dependabot exemption (#13, ADR 0020).
- Phase 7 done: accessibility (#17), long solutions (#16), endpoint security review (no authorization or
  validation finding besides the body cap). Docs, screenshots, owner logo, manifest (`beta`): PR #18 open.
- Phase 7 pending: merge #18 and release 0.1.0. `main` is `6943bec`; release PR #3 is open, NOT merged.
- Clean install check (2026-11-25, archive built from the #18 content before the logo change, version
  0.0.0): install and activation on GLPI 11.0.12 and 12.0.1, e2e 192 passed, no plugin errors in the PHP
  log, uninstall left no tables, settings or rights.
- Facts the code relies on: two bundles (ADR 0019); use `Rights::has()` (int in GLPI 11, bool in 12);
  settings apply on the next page load (no server cache).
- Commits carry no `Co-Authored-By` trailers and PR texts no tool footer (RULES.md), even when a tool
  suggests them; commit scopes are limited to the commitlint list.

## In progress (max 10)

- PR #18 (`docs/release-ready`, `6607d5f`): every check passed except E2E, still running at session end.
- Branch `docs/session-close` (local only, from `6607d5f`): these context notes and ADR 0023. The owner
  chose a local commit without push. After #18 is merged, carry that commit to a branch from `main`.

## Next steps (max 20)

1. Run `gh pr checks 18`. The owner approved these steps on 2026-11-25 and then ended the session, so ask
   again before pushing, merging or publishing: squash-merge #18 (title only, blank body).
2. Wait for release-please to update PR #3 (version 0.1.0), build the archive from that branch and verify
   it on a clean GLPI (playbook `release.md`, step 5). Only the version should differ from the checked one.
3. Merge PR #3 (publishes 0.1.0 and the docs; public, hard to undo), then install the published archive.
4. Owner: submit `plugin.xml` to the GLPI marketplace; optionally set the repository homepage to the docs.
5. Ask the owner to accept ADR 0023 (fixed-name copy of the release archive).
6. After 0.1.0, candidates: skip the bootstrap request on the login page (it answers 401); delete the
   preference row when a user is purged; `ActorSearch` scans at most 100 users and the assignee check
   ignores account validity dates; solution templates (ADR 0022).

## Blockers and waiting on owner (max 10)

- Branch protection on `main` is not active (rulesets `[]` on 2026-11-25).

## Open questions (max 10)

- None. Settled 2026-11-25: Ctrl+N and Ctrl+P held together run the browser action by design (tap Ctrl,
  release, then the letter); the repository stays public; the quick close takes free text only.

## Environment status (max 15)

- Host: Arch Linux (kernel 7.3.4), Node 24, pnpm 12.16.1, podman 6.1.4, podman-compose 1.6.0, msgfmt; no
  PHP, no Docker. Containers stopped at session end (data kept): `make up`, then `make install-plugin`.
- Images pulled: mariadb:10.11, glpi/glpi:11.0.12 and 12.0.1, githubactions-glpi-apache php-8.2-glpi-11.0.x
  and php-8.3-glpi-12.0.x, githubactions-mariadb:10.11, playwright:v1.65.1-noble, mkdocs-material:9.8.1,
  actionlint:1.7.12.
- Playwright browsers on the host: chromium, firefox (webkit needs `make e2e-container`).
- Agent shells have no SSH agent socket: push over HTTPS with
  `git -c credential.helper= -c credential.helper='!gh auth git-credential' push https://github.com/scobjuan/ticketboost.git <branch>`.
- A local agent link exists (created with `pnpm agents:link`, excluded via `.git/info/exclude`).

## Session log (max 40)

- 2026-11-19 Phase 6a implemented on `feat/config-ui`, uncommitted. Owner chose two PRs and a separate bundle.
- 2026-11-25 Dependabot PR #10 replaced by #12 and exempted (#13). Phase 6a merged (#14).
- 2026-11-25 Phase 6b implemented, verified on all browsers and merged (#15).
- 2026-11-25 Phase 7: accessibility and body cap merged (#16, #17); owner decided ADR 0021 and 0022.
- 2026-11-25 Release preparation in PR #18, checked on clean GLPI installs. Stopped at: owner ended the
  session before merging. Next: "Next steps" 1.

## ADR index

- See `.agents/decisions/README.md`.
