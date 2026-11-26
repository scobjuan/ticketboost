# Project memory (max 250 lines, prune every session)

Updated: 2026-11-25 | Version: unreleased (target 0.1.0) | Phase: 6a merged, 6b committed

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- `main` at `6d4e536` (Phases 0 to 5 and 6a merged, PRs #4 to #14; Dependabot exemption is ADR 0020).
  Release PR #3 `chore(main): release 0.1.0` stays open: do NOT merge it before Phase 7.
- Phase 6 is split in two PRs (owner decision): 6a admin page, 6b user preference tab.
- ADR 0019 (accepted): configuration pages load a second bundle `public/js/ticketboost-config.js`
  (budget 10 KB gzip, 2.4 KB used; main bundle 9.1 of 15 KB). `pnpm build` runs both Vite configs
  (`vite.config.ts`, `vite.settings.config.ts`); `scripts/size-check.mjs` checks both budgets.
- Existing data layer reused by the UI: `PluginConfig`, `LeaderSettings`, `Preference`,
  `KeymapResolver::issues()`; the bootstrap has no server cache, so settings apply on the next page load.
- Use `Rights::has()` for `Session::haveRight()` (int in GLPI 11, bool in 12).
- Commits must not carry `Co-Authored-By` trailers (RULES.md), even when the tool suggests one; commit
  scopes are limited to the commitlint list. PR texts carry no tool footer either.

## In progress (max 10)

- Branch `feat/user-preferences` (from `main` at `6d4e536`), Phase 6b committed locally, NOT pushed:
  personal settings tab (ADR 0021, accepted), watch of AJAX forms, locales, docs, seed user `tech-es`,
  `e2e/specs/preferences.spec.ts`, and a fix of the admin page (deprecated `csrf_token()` on GLPI 12).
- Verified 2026-11-25: `make check` (Vitest 341; PHPUnit 293 tests on GLPI 11 and 12, 98.39%) and e2e
  156 passed (117 on glpi11-chromium, glpi12-chromium, glpi12-firefox; 39 on glpi12-webkit).

## Next steps (max 20)

1. Push `feat/user-preferences` and open the PR once the owner approves (PR title scope `config`).
2. After the merge, Phase 7 (PLAN section 8): axe, cross-browser, security review of the endpoints, docs
   with screenshots, marketplace metadata, release 0.1.0.
3. Decide on `SolutionTemplate` support for the quick close (the plan mentions "text or template"; Phase 5
   ships text only; the admin docs now say text).
4. Check how the bundle behaves on the login page (it loads there; bootstrap answers 401
   and the panel stays `off`): decide whether to skip the request when there is no session.
5. Verify branch protection on `main` once the owner saves it:
   `gh api repos/scobjuan/ticketboost/rulesets` must list an active ruleset.

## Blockers and waiting on owner (max 10)

- Branch protection on `main` is still not active (rulesets `[]` on 2026-11-12).

## Open questions (max 10)

- None.

## Environment status (max 15)

- Host: Arch Linux (kernel 7.3.4), Node 24, pnpm 12.16.1, podman 6.1.4,
  podman-compose 1.6.0, msgfmt; no PHP, no Docker. `make up` is running (GLPI 11 :8011,
  GLPI 12 :8012) with the plugin installed.
- Images pulled: mariadb:10.11, glpi/glpi:11.0.12 and 12.0.1, githubactions-glpi-apache
  php-8.2-glpi-11.0.x and php-8.3-glpi-12.0.x, githubactions-mariadb:10.11,
  playwright:v1.65.1-noble, mkdocs-material:9.8.1, actionlint:1.7.12.
- Playwright browsers on the host: chromium, firefox (webkit needs `make e2e-container`).
- Agent shells have no `SSH_AUTH_SOCK` and no agent socket under `~/.ssh/agent/`: push over
  HTTPS with
  `git -c credential.helper= -c credential.helper='!gh auth git-credential' push https://github.com/scobjuan/ticketboost.git <branch>`.
- A local agent link exists (created with `pnpm agents:link`, excluded via `.git/info/exclude`).

## Session log (max 40)

- 2026-11-18 Full e2e run found test pollution (see In progress); seed made self-cleaning. Stopped at:
  verifying with a second `make e2e`. Next: "Next steps" 0.
- 2026-11-19 Found why `navigation.spec.ts:110` flaked: the self-service home fills the session list and two
  tickets share `date_mod`, so the order varied. Spec now asserts the set of visited ids; e2e green (78).
  WebKit showed the post-reload notice after 6.3 s (5 s default expect): `expect.timeout` is now 15 s.
  PR #11 (Phase 5) passed CI and was squash-merged as `b75a6e4`.
- 2026-11-19 Phase 6a implemented on `feat/config-ui` (see In progress), uncommitted. Owner chose two PRs and a
  separate bundle. Stopped at: webkit run of `config.spec.ts` pending, then commits. Next: "Next steps" 1.
- 2026-11-25 PR #10 replaced by #12 and Dependabot exempted (#13, ADR 0020), both merged. Phase 6a
  verified on all browsers and squash-merged as `6d4e536` (#14). Stopped at: start of Phase 6b.
  Next: "Next steps" 1.
- 2026-11-25 Phase 6a merged as `6d4e536` (#14). Phase 6b implemented and verified on all browsers,
  committed on `feat/user-preferences`. Stopped at: owner approval to push. Next: "Next steps" 1.

## ADR index

- See `.agents/decisions/README.md`.
