# Project memory (max 250 lines, prune every session)

Updated: 2026-11-12 | Version: unreleased (target 0.1.0) | Phase: 0 done and published, Phase 1 next

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- Phase 0 pushed to github.com/scobjuan/ticketboost (public). `main` at `4172267`.
- First GitHub Actions run fully green: 4 PHP jobs (setfacl + sudo as www-data work on
  GitHub runners), E2E on GLPI 11 and 12, frontend, docs, workflow lint, release dry run.
- Release workflow works with `RELEASE_PLEASE_TOKEN` (fine-grained PAT: Contents,
  Pull requests and Issues read and write). Release PR #3 `chore(main): release 0.1.0` is
  open with label `autorelease: pending`. Do NOT merge it before Phase 7: merging tags
  v0.1.0 and publishes the release. release-please updates it on every push to `main`.
- PR #2 (first release PR) closed and its branch deleted: it had the release-please
  default robot header and tool footer. Fixed with neutral `pull-request-header` /
  `pull-request-footer` in `release-please-config.json`.
- Dependabot PR #1 (TypeScript 6 -> 7) closed: typescript-eslint 8 does not support TS 7.
- Repository settings done by owner: public, squash merge only (PR title), Pages source
  GitHub Actions (site appears with the first release).

## In progress (max 10)

- Local branch `chore/session-2026-11-12` (not pushed): dependabot ignore for TypeScript
  major updates + this memory update. Needs owner approval to push and open a PR.

## Next steps (max 20)

1. Owner reboots (see Blockers), then verify the compose path: `make up`,
   `make install-plugin`, `make e2e`, `make test-php`.
2. Verify branch protection on `main` once the owner saves it:
   `gh api repos/scobjuan/ticketboost/rulesets` must list an active ruleset with the
   11 required checks, linear history, PR required, force push blocked.
3. With owner approval: push `chore/session-2026-11-12` and open its PR.
4. Phase 1 - keys engine and modal (PLAN.md section 8) on branch `feat/leader-key`:
   start with `frontend/src/keys/leader.ts` state machine and its tests.

## Blockers and waiting on owner (max 10)

- Host needs a reboot: the kernel was upgraded 7.2.7 -> 7.2.15 on 2026-11-11 while
  running 7.2.7, so its modules are gone and podman fails ("overlay is not supported").
- Branch protection on `main` is not active yet (no classic rule, no ruleset found).
- Approval to push branches and open PRs (AGENTS.md rule 6).

## Open questions (max 10)

- None.

## Environment status (max 15)

- Host: Arch Linux, Node 24, pnpm 12.16.1, podman 6.1.4, podman-compose 1.6.0, msgfmt;
  no PHP, no Docker.
- Images pulled: mariadb:10.11, glpi/glpi:11.0.12 and 12.0.1, githubactions-glpi-apache
  php-8.2-glpi-11.0.x and php-8.3-glpi-12.0.x, githubactions-mariadb:10.11,
  playwright:v1.65.0-noble, mkdocs-material:9.8.1, actionlint:1.7.12.
- Playwright browsers installed on host: chromium, firefox (webkit needs `make e2e-container`).
- Agent shells start without `SSH_AUTH_SOCK`: export it from `~/.ssh/agent/s.*` before
  `git push`. `gh` is authenticated separately over HTTPS.
- A local agent link exists (created with `pnpm agents:link`, excluded via `.git/info/exclude`).

## Session log (max 40)

- 2026-11-09 Planning interview, plan approved; Phase 0 implemented and verified against
  real GLPI 11 and 12. Stopped at: owner review.
- 2026-11-12 Phase 0 pushed, CI/CD verified on GitHub, release-please fixed (neutral PR
  texts), PR #1 and #2 closed. Stopped at: compose verification blocked by pending reboot.
  Next: "Next steps" 1.

## ADR index

- See `.agents/decisions/README.md`.
