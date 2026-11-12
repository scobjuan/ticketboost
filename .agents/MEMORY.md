# Project memory (max 250 lines, prune every session)

Updated: 2026-11-12 | Version: unreleased (target 0.1.0) | Phase: 2 implemented, owner review

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- `main` at `4591dbe` (Phase 0 and Phase 1 merged, PRs #4 and #5). CI green. Release PR #3
  `chore(main): release 0.1.0` stays open: do NOT merge it before Phase 7.
- Phase 2 (backend core) implemented on branch `feat/backend-core`, pushed with its PR open (squash-merge after owner review):
  installer (table `glpi_plugin_ticketboost_preferences`, right `plugin_ticketboost`,
  default config), Profile tab, `PluginConfig`, `Preference`, PHP keymap validator and
  resolver (shared cases with TS in `shared/keymap-cases.json`), `RequestGuard`,
  `ajax/bootstrap.php`, labels in en_GB and es_ES, API client and `startPanel()` in the
  frontend. The panel now opens on real GLPI 11 and 12 (leader tap, `?` overview, Esc).
- Verified: PHPUnit 109 tests, 99.5% line coverage, on GLPI 11 and 12; Vitest 238 tests,
  99% coverage; bundle about 7.8 KB gzip of 15 KB; e2e 21/21 on glpi11-chromium,
  glpi12-chromium, glpi12-firefox, plus 7/7 on glpi12-webkit (`make e2e-container`); plugin install, uninstall and reinstall clean with the
  real console on both versions.
- The panel only offers `help` today (`ActionAvailability::IMPLEMENTED`): the root menu is
  empty and `?` opens the overview. Each later phase adds its actions to that list.
- ADR 0018 is `proposed`: it drops `ajax/context.php` from the Phase 2 plan (the page URL
  already gives the context) and records the CSRF, self-service and rights findings.

## In progress (max 10)

- `feat/backend-core` (pushed, PR open): waiting for owner review and merge; confirm
  ADR 0018 on the PR (then set its status to `accepted`).

## Next steps (max 20)

1. Owner reviews the Phase 2 PR and confirms ADR 0018; after the squash merge, sync `main`
   and delete the merged remote branches `feat/leader-key` and `feat/backend-core`.
2. Phase 3 - navigation (PLAN.md section 8) on `feat/navigation`: seed data for e2e
   (users `tech1`, `tech2`, `selfservice`, about 6 tickets), `ajax/navigate.php`
   (circular next/previous over `$_SESSION['glpilistitems']['Ticket']`, fallback list),
   actions `next`, `previous`, `list`, `my_tickets` (add them to
   `ActionAvailability::IMPLEMENTED`), frontend action registry replacing the throwing
   `run` in `startPanel`, e2e specs. First POST/GET action endpoint also gets the CSRF
   e2e spec (ADR 0018).
3. Phase 6 reminder: key recorder and conflict UI use `KeymapResolver::issues()`; admin
   page needs `front/config.form.php` and the `config_page` hook (not registered yet).
4. Check how the bundle behaves on the login page (it loads there; bootstrap answers 401
   and the panel stays `off`): decide whether to skip the request when there is no session.
5. Verify branch protection on `main` once the owner saves it:
   `gh api repos/scobjuan/ticketboost/rulesets` must list an active ruleset.

## Blockers and waiting on owner (max 10)

- Review and merge of the Phase 2 PR, decision on ADR 0018 (dropping `ajax/context.php`).
- Branch protection on `main` is still not active (rulesets `[]` on 2026-11-12).
- Remote branch `feat/leader-key` is merged and can be deleted (owner to confirm).

## Open questions (max 10)

- None.

## Environment status (max 15)

- Host: Arch Linux (kernel 7.2.15), Node 24, pnpm 12.16.1, podman 6.1.4,
  podman-compose 1.6.0, msgfmt; no PHP, no Docker. `make up` is running (GLPI 11 :8011,
  GLPI 12 :8012) with the plugin installed.
- Images pulled: mariadb:10.11, glpi/glpi:11.0.12 and 12.0.1, githubactions-glpi-apache
  php-8.2-glpi-11.0.x and php-8.3-glpi-12.0.x, githubactions-mariadb:10.11,
  playwright:v1.65.0-noble, mkdocs-material:9.8.1, actionlint:1.7.12.
- Playwright browsers on the host: chromium, firefox (webkit needs `make e2e-container`).
- Agent shells have no `SSH_AUTH_SOCK` and no agent socket under `~/.ssh/agent/`: push over
  HTTPS with
  `git -c credential.helper= -c credential.helper='!gh auth git-credential' push https://github.com/scobjuan/ticketboost.git <branch>`.
- A local agent link exists (created with `pnpm agents:link`, excluded via `.git/info/exclude`).

## Session log (max 40)

- 2026-11-09 Planning interview, plan approved; Phase 0 implemented and verified against
  real GLPI 11 and 12. Stopped at: owner review.
- 2026-11-12 Phase 0 pushed, CI/CD verified on GitHub, release-please fixed (neutral PR
  texts), PR #1 and #2 closed. Stopped at: compose verification blocked by pending reboot.
- 2026-11-12 PR #4 and #5 merged; compose path verified; Phase 1 (keys engine and panel)
  implemented, reviewed and merged with ADRs 0016-0017.
- 2026-11-12 Phase 2 implemented on `feat/backend-core` with ADR 0018 (proposed). Found and
  fixed on real GLPI: helpdesk rights whitelist, `haveRight` int vs bool, `tableExists`
  cache. Phase 2 pushed and PR opened. Stopped at: owner review of the PR. Next: "Next steps" 1.

## ADR index

- See `.agents/decisions/README.md`.
