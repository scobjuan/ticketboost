# Project memory (max 250 lines, prune every session)

Updated: 2026-11-19 | Version: unreleased (target 0.1.0) | Phase: 4 merged, Phase 5 implemented

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- `main` at `9ba1f26` (Phases 0 to 4 merged, PRs #4 to #9). Release PR #3
  `chore(main): release 0.1.0` stays open: do NOT merge it before Phase 7.
- Phase 3 (navigation) and Phase 4 (assignment) are merged; ADR 0004 and ADR 0018 apply.
- Phase 4 built `ajax/action.php` (POST, JSON body), `ajax/search.php` (GET) and `src/Assignment/`.
- Phase 5 (ticket actions) is implemented on `feat/ticket-actions` (local commits, NOT pushed):
  `src/Ticketing/` (`TicketActions`, `TicketActionRequest`), `src/Action/ActionRouter.php` (the endpoint
  dispatcher), `Bootstrap\Options` (status and priority choices) and `closeMode` in the payload; frontend
  text view for the solution, `editor/compose.ts` for followup and task; all five actions are in
  `IMPLEMENTED`. The server requires the central interface and checks each right on the ticket.
- Close default is `quick_solution` (owner decision); the admin switch arrives with Phase 6.
- Verified locally: PHPUnit 258 tests on GLPI 11 and 12 (98%), Vitest 300 (99%), lint, bundle 9.0 of
  15 KB gzip; `ticket-actions.spec.ts` green on glpi11-chromium and glpi12-chromium.
- Use `Rights::has()` for `Session::haveRight()` (int in GLPI 11, bool in 12).
- Commits must not carry `Co-Authored-By` trailers (RULES.md); commit scopes are limited to
  the commitlint list. The `gh` token has the `workflow` scope (2026-11-15).

## In progress (max 10)

- `feat/ticket-actions`: Phase 5 done. Two local commits pending push: the self-cleaning seed
  (`e2e/seed/seed.php`) and the order-independent self-service walk in `navigation.spec.ts`.
  Full e2e on glpi11-chromium, glpi12-chromium and glpi12-firefox: 78 passed (2026-11-19).
  Project names: `glpi11-chromium`, `glpi12-chromium`, `glpi12-firefox`, `glpi12-webkit`.

## Next steps (max 20)

0. Check webkit with `make e2e-container ARGS="--project=glpi12-webkit"` (the host cannot launch it).
1. Push `feat/ticket-actions` and open the PR once the owner approves; after CI is green and the owner
   approves, squash-merge.
2. Phase 6 reminder: key recorder and conflict UI use `KeymapResolver::issues()`; admin
   page needs `front/config.form.php` and the `config_page` hook (not registered yet).
3. Check how the bundle behaves on the login page (it loads there; bootstrap answers 401
   and the panel stays `off`): decide whether to skip the request when there is no session.
4. Verify branch protection on `main` once the owner saves it:
   `gh api repos/scobjuan/ticketboost/rulesets` must list an active ruleset.
5. Phase 6 must also: add the e2e for `close_mode = direct_close` (needs the admin page to set it; the
   PHPUnit test and the Vitest app test already cover it), and decide on `SolutionTemplate` support for
   the quick close (the plan mentions "text or template"; Phase 5 ships text only).

## Blockers and waiting on owner (max 10)

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
- 2026-11-15 Token refreshed, PR #8 (Phase 3) green on CI and squash-merged as `8b4f7cd`. Phase 4 implemented on
  `feat/assignment` with the stale-picker fix; PR #9 green and squash-merged as `9ba1f26`.
- 2026-11-16 Phase 5 implemented on `feat/ticket-actions`. Found on real GLPI: the followup editor must be
  focused only after TinyMCE initializes; self-service reached the status check (now 403 first).
  Stopped at: full e2e run, then owner approval to push. Next: "Next steps" 1.

- 2026-11-18 Full e2e run found test pollution (see In progress); seed made self-cleaning. Stopped at:
  verifying with a second `make e2e`. Next: "Next steps" 0.
- 2026-11-19 Found why `navigation.spec.ts:110` flaked: the self-service home fills the session list and two
  tickets share `date_mod`, so the order varied. Spec now asserts the set of visited ids; e2e green (78).
  Stopped at: owner approval to push. Next: "Next steps" 0 and 1.

## ADR index

- See `.agents/decisions/README.md`.
