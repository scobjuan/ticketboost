# Project memory (max 250 lines, prune every session)

Updated: 2026-11-09 | Version: unreleased (target 0.1.0) | Phase: 0 - Foundation (done, awaiting owner review)

Live state for the next session. Keep it short and current; git keeps the history.
Decisions go to `.agents/decisions/`, reusable knowledge to `.agents/KNOWLEDGE.md`.

## Current state (max 20)

- Phase 0 complete on local `main` (nothing pushed). Waiting for owner review.
- Plugin skeleton installs and activates on GLPI 11.0.12 and 12.0.1; bundle loads on
  central and self-service (smoke e2e green in chromium, firefox, webkit).
- PHP gate (`scripts/php-ci.sh`) green in the official CI images: GLPI 11 / PHP 8.2 and
  GLPI 12 / PHP 8.3 (parallel-lint, php-cs-fixer, PHPStan max, Rector, headers, PHPUnit 100%).
- Frontend gate green: ESLint, Prettier, tsc, Vitest 100% coverage, bundle 257 B gzip.
- Release archive (`make release-dry`) installs and works on a clean GLPI 12 (read-only mount).
- Docs build with MkDocs strict; workflows pass actionlint.
- Agent setup complete: AGENTS.md, RULES, PLAN, MEMORY, KNOWLEDGE, 15 ADRs, 7 playbooks,
  templates, `agents:check` guard (pre-commit, commit-msg, CI), `agents:link`.

## In progress (max 10)

- None.

## Next steps (max 20)

1. Owner reviews Phase 0 (commits on local `main`).
2. Verify the compose path once podman-compose is installed: `make up`,
   `make install-plugin`, `make e2e`, `make test-php` (so far verified with equivalent
   `podman run` commands, not through compose).
3. With owner approval: push `main`; owner applies the one-time GitHub setup in
   `docs/development/ci-cd.md` (secret `RELEASE_PLEASE_TOKEN`, Pages, squash only,
   branch protection with the required checks).
4. Watch the first GitHub Actions run; the `php` job (container + setfacl + sudo as
   www-data) and the e2e job (docker compose) are not yet verified on GitHub runners.
5. Phase 1 - keys engine and modal (PLAN.md section 8): start with
   `frontend/src/keys/leader.ts` state machine and its tests.

## Blockers and waiting on owner (max 10)

- Install podman-compose: `sudo pacman -S podman-compose`.
- Approval to push to github.com/scobjuan/ticketboost and to configure the repository.
- Decide when the repository becomes public (required before the first release and Pages).

## Open questions (max 10)

- None.

## Environment status (max 15)

- Host: Arch Linux, Node 24, pnpm 12.16.1, podman 6.1.4, msgfmt; no PHP, no Docker,
  no podman-compose yet.
- Images pulled: mariadb:10.11, glpi/glpi:11.0.12 and 12.0.1, githubactions-glpi-apache
  php-8.2-glpi-11.0.x and php-8.3-glpi-12.0.x, githubactions-mariadb:10.11,
  playwright:v1.65.0-noble, mkdocs-material:9.8.1, actionlint:1.7.12.
- Playwright browsers installed on host: chromium, firefox (webkit needs `make e2e-container`).
- GitHub: repo scobjuan/ticketboost private and empty; no secrets, Pages or protection.
- A local agent link exists (created with `pnpm agents:link`, excluded via `.git/info/exclude`).

## Session log (max 40)

- 2026-11-09 Planning interview, plan approved; Phase 0 implemented and verified against
  real GLPI 11 and 12. Stopped at: owner review. Next: "Next steps" 1.

## ADR index

- See `.agents/decisions/README.md`.
