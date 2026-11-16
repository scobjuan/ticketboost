# Project memory (max 250 lines, prune every session)

Updated: 2026-11-15 | Version: unreleased (target 0.1.0) | Phase: 3 merged, Phase 4 in progress

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- `main` at `8b4f7cd` (Phases 0 to 3 merged, PRs #4 to #8). Release PR #3
  `chore(main): release 0.1.0` stays open: do NOT merge it before Phase 7.
- Phase 3 (navigation) is merged; its fallback follows ADR 0004. ADR 0018 is `accepted`.
- Phase 4 (assignment) is implemented on `feat/assignment` (local commits, NOT pushed):
  `ajax/action.php` (POST, JSON body), `ajax/search.php` (GET), `src/Assignment/`; the four actions
  are in `ActionAvailability::IMPLEMENTED`; `actions/handlers.ts` posts and searches; picker fix:
  Enter does nothing while a search is pending (it chose from a stale list).
- Rights as built: `assign_me`/`unassign_me` need update on the ticket (`assign_me` also assign or
  own/steal); `assign_other`/`unassign_others` need assign. The seeded `tech` has own but not
  assign, so it only gets `assign_me`/`unassign_me` (e2e covers it).
- Verified locally: PHPUnit 224 tests on GLPI 11 and 12 (98.7%), Vitest 273 (99%), lint, bundle
  8.3 of 15 KB gzip, `assignment.spec.ts` green on glpi11-chromium, glpi12-chromium, glpi12-firefox;
  the CSRF spec proves a forged POST is refused on both versions (ADR 0018).
- Use `Rights::has()` for `Session::haveRight()` (int in GLPI 11, bool in 12).
- Commits must not carry `Co-Authored-By` trailers (RULES.md); commit scopes are limited to
  the commitlint list. The `gh` token has the `workflow` scope (2026-11-15).

## In progress (max 10)

- `feat/assignment`: Phase 4 done and committed locally; waiting for owner approval to push and open the PR.

## Next steps (max 20)

1. Owner approves the push of `feat/assignment`; push over HTTPS and open the PR; after CI is green and
   the owner approves, squash-merge.
2. Phase 5 - ticket actions (PLAN.md section 8): `change_status` (submenu), `close` (both modes,
   ADR 0006), `change_priority` (submenu), `followup` / `task` editor focus. They need `options` in the
   bootstrap payload (`SubmenuOption`) and the same POST endpoint `ajax/action.php`; generalize
   `AssignmentRequest` or add a sibling request class.
3. Phase 6 reminder: key recorder and conflict UI use `KeymapResolver::issues()`; admin
   page needs `front/config.form.php` and the `config_page` hook (not registered yet).
4. Check how the bundle behaves on the login page (it loads there; bootstrap answers 401
   and the panel stays `off`): decide whether to skip the request when there is no session.
5. Verify branch protection on `main` once the owner saves it:
   `gh api repos/scobjuan/ticketboost/rulesets` must list an active ruleset.
6. The e2e "self-service user only walks their own tickets" timed out once on glpi11-chromium under
   load and passed alone: watch it for flakiness.

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
  `feat/assignment` with the stale-picker fix. Stopped at: owner approval to push. Next: "Next steps" 1.

## ADR index

- See `.agents/decisions/README.md`.
