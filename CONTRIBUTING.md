# Contributing to TicketBoost

Thank you for helping. This guide covers everything needed to get a change merged.

## Before you start

- For new features or changes of behavior, open an issue first so the scope can be agreed.
- Read [.agents/RULES.md](.agents/RULES.md): the project rules apply to every contributor.
- Be kind; see the [Code of Conduct](CODE_OF_CONDUCT.md).

## Set up

Follow [docs/development/local-environment.md](docs/development/local-environment.md):
`make install`, `make up`, `make install-plugin`.

## Making a change

1. Branch from `main`: `feat/<topic>`, `fix/<topic>`, `docs/<topic>`.
2. Write the code and its tests (Vitest, PHPUnit, Playwright as relevant).
3. Every user-facing string goes through gettext and exists in `en_GB.po` and `es_ES.po`.
4. Update `docs/` when behavior changes.
5. Run `make check` and `make e2e`.

## Commits and pull requests

- [Conventional Commits](https://www.conventionalcommits.org):
  `type(scope): subject`, for example `feat(actions): add assign to me`.
  Types: feat, fix, perf, refactor, test, docs, build, ci, chore, revert.
  Scopes: keys, ui, actions, nav, config, i18n, install, api, e2e, ci, docs, agents, deps, release.
- The pull request title is also a Conventional Commit: it becomes the squash commit.
- Breaking changes (removed action, changed default binding, dropped GLPI or PHP version)
  use `!` and describe the migration.
- Do not add tool attribution trailers or signatures, and no emojis outside commit messages.
- Git hooks run lint, rule guards and commitlint; CI runs the full suite and must be green.

## Style

- PHP: GLPI coding standard, PHPStan level max, license header from `tools/HEADER`.
- TypeScript: strict, ESLint + Prettier (single quotes, no semicolons), named exports, JSDoc
  on exported functions.
- English for code, comments, docs and commits.

## Releases

Maintainers merge the release pull request opened by release-please; versions follow
[Semantic Versioning](https://semver.org). See
[docs/development/ci-cd.md](docs/development/ci-cd.md).
