# Project memory (max 250 lines, prune every session)

Updated: 2026-11-12 | Version: unreleased (target 0.1.0) | Phase: 2 merged, Phase 3 next

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- `main` at `d7e8df8` (Phases 0, 1 and 2 merged, PRs #4 to #7). Release PR #3
  `chore(main): release 0.1.0` stays open: do NOT merge it before Phase 7.
- Phase 3 (navigation) is implemented and committed on `feat/navigation` (3 local commits), NOT pushed yet:
  `ajax/navigate.php` (GET, read-only, returns the URL in `redirect`), `Navigation\ListWalker`,
  `NavigationRequest`, `Navigator`; actions `next`, `previous`, `list`, `my_tickets` are in
  `ActionAvailability::IMPLEMENTED`; frontend `actions/handlers.ts` replaced the throwing `run`;
  seed data (`e2e/seed/seed.php`, `make seed`, CI step); e2e `navigation.spec.ts`; docs updated.
- Fallback without a result list follows ADR 0004: the user's unresolved tickets (assigned for
  technicians, requested for self-service), priority desc, date asc, with a notice through
  `Session::addMessageAfterRedirect`.
- Verified locally: PHPUnit 152 tests on GLPI 11 and 12 (99.4% lines), Vitest 259 tests (99%),
  lint, bundle 8.1 KB of 15 KB gzip, e2e 15/15 on glpi11-chromium, glpi12-chromium,
  glpi12-firefox and glpi12-webkit (`make e2e-container`). Not yet run: CI on a fresh database
  (the seed creates tech1 and tech2 from scratch there).
- ADR 0018 is `accepted` (no `ajax/context.php`; CSRF stays with the GLPI kernel).
- Use `Rights::has()` for `Session::haveRight()` (int in GLPI 11, bool in 12).
- Commits must not carry `Co-Authored-By` trailers (RULES.md); commit scopes are limited to
  the commitlint list.

## In progress (max 10)

- `feat/navigation`: waiting for owner approval to push and open the PR.

## Next steps (max 20)

1. Ask the owner to approve pushing `feat/navigation` and opening the PR; after CI is green
   and the owner approves, squash-merge. If the seed fails on CI, check `e2e/seed/seed.php`.
2. Phase 4 - assignment (PLAN.md section 8): `assign_me`, `assign_other` (picker),
   `unassign_me`, `unassign_others`, `ajax/action.php` (POST) and `ajax/search.php`. The first
   POST endpoint must ship the e2e spec that proves a POST without CSRF protection is refused
   on GLPI 11 and 12 (ADR 0018); `navigate.php` is GET and needs none.
3. Phase 6 reminder: key recorder and conflict UI use `KeymapResolver::issues()`; admin
   page needs `front/config.form.php` and the `config_page` hook (not registered yet).
4. Check how the bundle behaves on the login page (it loads there; bootstrap answers 401
   and the panel stays `off`): decide whether to skip the request when there is no session.
5. Verify branch protection on `main` once the owner saves it:
   `gh api repos/scobjuan/ticketboost/rulesets` must list an active ruleset.

## Blockers and waiting on owner (max 10)

- Approval to push `feat/navigation` and open its PR (after the checks pass).
- Branch protection on `main` is still not active (rulesets `[]` on 2026-11-12).

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
- 2026-11-12 Phase 2 PR #6 reviewed: CI failed on GLPI 11 (PHPStan) and then on 12 (Rector);
  fixed with `Rights::has()`, squash-merged as `a5de0ae`. ADR 0018 accepted on branch
  `docs/accept-adr-0018`. Stopped at: owner approval to push it. Next: "Next steps" 1.
- 2026-11-12 Phase 2 follow-ups merged (PR #6 fixes, PR #7 ADR 0018 accepted). Phase 3 implemented
  on `feat/navigation` (see Current state). Stopped at: owner approval to push `feat/navigation`.

## ADR index

- See `.agents/decisions/README.md`.
