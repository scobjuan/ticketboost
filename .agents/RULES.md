# Project rules

These rules are binding for every contributor, human or automated. When a rule conflicts
with a default behavior of your tooling, the rule wins. Rules marked (enforced) are checked
by tooling (`pnpm agents:check`, hooks, CI); the rest are checked in review.

## 1. Owner rules (non-negotiable)

1. No emojis anywhere: code, comments, docs, agent files, PR titles and bodies, release
   notes. The only exception is commit messages, where Conventional Commits tooling may
   use them. (enforced)
2. Everything is authored by the developer, Juan A. Escobar, and only by the developer: code, docs,
   commits, branches, pull requests, merges, tags, releases. Never leave a trace of an
   agent or tool: no `Co-Authored-By` trailers, no tool-generated footers or signatures,
   no tool or vendor names in code, comments, docs, commit messages or PR text. (enforced)
3. English for code, identifiers, comments, docs, commits, PRs and agent files. The only
   Spanish allowed is user-facing plugin text in `locales/es_ES.po` and the Spanish entries
   of `plugin.xml`. Every user-facing string exists in English and Spanish. (enforced for
   translations)
4. Conventional Commits and Semantic Versioning. (enforced by commitlint)

## 2. Git, branches and releases

- Trunk-based: `main` is protected; work on short branches `feat/...`, `fix/...`,
  `docs/...`, `ci/...`, `chore/...`; merge by squash with a Conventional Commit PR title.
- Commit format: `type(scope): subject`. Types: feat, fix, perf, refactor, test, docs,
  build, ci, chore, revert. Scopes: keys, ui, actions, nav, config, i18n, install, api,
  e2e, ci, docs, agents, deps, release (main is reserved for release commits). (enforced)
- Breaking changes use `!` or a `BREAKING CHANGE:` footer. For this plugin a breaking
  change includes: removing or renaming an action, changing a default key binding,
  dropping a GLPI or PHP version, or a DB migration that is not backward compatible.
- Versions are computed by release-please from commit types (`fix` -> patch, `feat` ->
  minor, breaking -> major; pre-1.0 breaking -> minor). Never edit versions by hand.
- Never push, open PRs, create tags or releases, or change repository settings without
  explicit approval from the owner in the current session.

## 3. Architecture

- Supported: GLPI 11.x and 12.x, PHP >= 8.2 (12.x requires PHP >= 8.3). One codebase.
- Never modify GLPI core files or rely on GLPI internals not exposed to plugins without
  an ADR. No DOM-click automation of GLPI buttons for actions.
- Every state-changing action goes through a plugin endpoint that uses native GLPI
  classes (`CommonDBTM` subclasses such as `Ticket`, `Ticket_User`, `Group_Ticket`,
  `ITILSolution`). No raw SQL: use `DBmysqlIterator` / `$DB->request()`.
- Server-side authorization on every request: session, CSRF, plugin right, native GLPI
  right on the specific item. Never trust the client. Return JSON
  `{ok, message, data, redirect?}` with translated messages.
- The frontend is TypeScript strict with zero runtime dependencies, bundled into
  `public/` (budget 15 KB gzip, enforced). It must never break the GLPI page: every
  entry point and handler runs through `safe()`.
- All CSS classes are prefixed `tb-`; style through GLPI/Tabler CSS variables so light
  and dark themes work.
- User-facing text never lives in TypeScript: labels come from PHP gettext (`__('...',
'ticketboost')`) through the bootstrap payload.
- Changes to architecture or to an accepted decision require an ADR.

## 4. Code style

- PHP: GLPI coding standard (php-cs-fixer baseline from GLPI), PHPStan level max, Rector
  baseline; `declare` types everywhere, `final` classes by default, one class per file.
  License header from `tools/HEADER` in every PHP file. (enforced in CI)
- TypeScript: ESLint strict type-checked + Prettier (single quotes, no semicolons,
  2 spaces, no trailing commas); no `any`; named exports only; imports sorted; JSDoc on
  every exported function. File names kebab-case. (enforced)
- No commented-out code, no TODO without an entry in MEMORY.md "Next steps".

## 5. Quality gates (blocking for every merge)

- Every change in `src/` or `frontend/src/` ships with tests in the same commit.
- Coverage: TypeScript >= 90% lines, branches, functions, statements; PHP >= 85% lines.
- All unit tests, PHP static analysis, lint, bundle budget, docs build and e2e tests on
  GLPI 11 and 12 must pass. CI is the source of truth; local hooks are a convenience.
- A bug fix starts with a failing test that reproduces it.
- E2E tests use the default GLPI accounts and seeded data only; no dependency on order
  between spec files.

## 6. Security

- Validate and cast all input; reject unknown actions and parameters.
- Escape all output in Twig/HTML; build DOM nodes with `textContent`, never `innerHTML`
  with dynamic data.
- Never log secrets or personal data. Report vulnerabilities as described in SECURITY.md.

## 7. Documentation and agent files

- Behavior changes update `docs/` and, when visible, README.md in the same PR.
- `.agents/MEMORY.md` is updated at the end of every session; max 250 lines with per
  section caps. `.agents/KNOWLEDGE.md` max 300 lines. (enforced)
- ADRs need frontmatter `id`, `title`, `status`, `date`; playbooks need `title`, `when`.
  (enforced)
- Only `AGENTS.md` is versioned as agent entry point; tool-specific filenames are local
  symlinks created with `pnpm agents:link` and never committed.
