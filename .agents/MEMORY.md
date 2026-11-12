# Project memory (max 250 lines, prune every session)

Updated: 2026-11-12 | Version: unreleased (target 0.1.0) | Phase: 1 implemented, owner review

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- Phase 0 is on `main` at github.com/scobjuan/ticketboost (public); CI fully green.
- PR #4 (dependabot ignore for TypeScript majors) squash-merged: `main` at `3480f6b`.
- Phase 1 (keys engine and panel, TS only) implemented on local branch `feat/leader-key`,
  rebased on `main` (4 commits): leader state machine, binding notation, catalog
  stub, keymap validator with conflicts, panel (menus, breadcrumb, pickers, overview,
  focus trap), toasts, TinyMCE bridge, Ctrl+Enter. 154 Vitest tests, coverage 99.4%
  lines / 97.4% branches. Bundle 1.8 KB gzip (full app measured at about 6.5 KB).
- The panel is not mounted on real pages yet: `startApp()` needs the bootstrap payload
  (labels, rights-filtered actions, keymap) from Phase 2. Ctrl+Enter is live from `boot()`.
- ADR 0016 (Ctrl+Enter only where GLPI does not already handle it) and ADR 0017 (binding
  notation: `code` or typed character) added.
- Compose path verified after the reboot: `make up`, `make install-plugin`, `make e2e`
  (8/8, WebKit through `make e2e-container`).
- Release PR #3 `chore(main): release 0.1.0` stays open; do NOT merge before Phase 7.

## In progress (max 10)

- `feat/leader-key` (not pushed, rebased on `main`): Phase 1, waiting for owner review.
  Local branch `backup/leader-key-pre-rebase` can be deleted once the PR is open.

## Next steps (max 20)

1. With owner approval: push `feat/leader-key` and open its PR (title
   `feat(keys): add leader key engine and shortcut panel`).
2. Phase 2 - backend core (PLAN.md section 8) on branch `feat/backend-core`: installer,
   rights + Profile tab, admin config, preferences table, `ajax/bootstrap.php` returning
   `AppConfig` (see `frontend/src/app.ts`), RequestGuard, PHP keymap validator mirroring
   `frontend/src/keymap/` (ADR 0017), msgids for every `ui.*`, `group.*`, `action.*`,
   `message.*` label key used by the frontend.
3. Phase 2 frontend: fetch the bootstrap payload in `boot()` and call `startApp()`; add
   the first panel e2e spec (leader tap opens the panel, Ctrl+C does not, Esc closes).
4. E2E for Ctrl+Enter on a task form once Phase 3 seeds tickets.
5. Verify branch protection on `main` once the owner saves it:
   `gh api repos/scobjuan/ticketboost/rulesets` must list an active ruleset with the
   required checks, linear history, PR required, force push blocked.

## Blockers and waiting on owner (max 10)

- Review of Phase 1 and approval to push `feat/leader-key` and open its PR.
- Branch protection on `main` is still not active (rulesets `[]`, classic rule 404 on
  2026-11-12).

## Open questions (max 10)

- None.

## Environment status (max 15)

- Host: Arch Linux (kernel 7.2.15), Node 24, pnpm 12.16.1, podman 6.1.4,
  podman-compose 1.6.0, msgfmt; no PHP, no Docker.
- Images pulled: mariadb:10.11, glpi/glpi:11.0.12 and 12.0.1, githubactions-glpi-apache
  php-8.2-glpi-11.0.x and php-8.3-glpi-12.0.x, githubactions-mariadb:10.11,
  playwright:v1.65.0-noble, mkdocs-material:9.8.1, actionlint:1.7.12.
- Playwright browsers installed on host: chromium, firefox (webkit needs `make e2e-container`).
- Agent shells start without `SSH_AUTH_SOCK`; when no agent socket exists under
  `~/.ssh/agent/`, push over HTTPS with
  `git -c credential.helper= -c credential.helper='!gh auth git-credential' push https://github.com/scobjuan/ticketboost.git <branch>`.
- A local agent link exists (created with `pnpm agents:link`, excluded via `.git/info/exclude`).

## Session log (max 40)

- 2026-11-09 Planning interview, plan approved; Phase 0 implemented and verified against
  real GLPI 11 and 12. Stopped at: owner review.
- 2026-11-12 Phase 0 pushed, CI/CD verified on GitHub, release-please fixed (neutral PR
  texts), PR #1 and #2 closed. Stopped at: compose verification blocked by pending reboot.
- 2026-11-12 PR #4 opened (green); compose path verified; Phase 1 implemented on
  `feat/leader-key` with ADRs 0016-0017; PR #4 merged and branch rebased.
  Stopped at: owner review of Phase 1.
  Next: "Next steps" 1.

## ADR index

- See `.agents/decisions/README.md`.
