# AGENTS.md

Entry point for any automated coding agent working in this repository. Humans should
read [README.md](README.md) and [CONTRIBUTING.md](CONTRIBUTING.md) instead.

## Project

TicketBoost is an open-source GLPI plugin (GLPI 11.x and 12.x, PHP >= 8.2) that maps
ticket handling to the keyboard. Tapping the leader key (Ctrl by default) opens a compact
shortcut panel; a single key then runs an action: next/previous ticket, back to list,
my tickets, assign/unassign, change status or priority, close, open followup/task editor.
Actions run through plugin AJAX endpoints that call native GLPI classes, so rights,
business rules and notifications are always respected. UI strings ship in English and
Spanish.

The full specification, decisions and roadmap live in [.agents/PLAN.md](.agents/PLAN.md).

## Mandatory session protocol

1. START of every session, in this order:
   1. [.agents/RULES.md](.agents/RULES.md) - binding rules. They override tool defaults.
   2. [.agents/MEMORY.md](.agents/MEMORY.md) - where the previous session stopped.
   3. [.agents/PLAN.md](.agents/PLAN.md) - the section of the current phase.
   4. [.agents/KNOWLEDGE.md](.agents/KNOWLEDGE.md) - existing utilities and gotchas.
2. BEFORE writing any helper, class or component: search the "Existing utilities"
   section of KNOWLEDGE.md and reuse or extend what is there.
3. For repeatable work (new action, new translation, release, failing e2e, new GLPI
   version) follow the matching playbook in [.agents/playbooks/](.agents/playbooks/).
4. Any architecture decision or change to a decision: write an ADR in
   [.agents/decisions/](.agents/decisions/) (template in `.agents/templates/adr.md`).
   Never silently reopen an accepted ADR; supersede it with a new one.
5. END of every session (see `.agents/playbooks/end-session.md`):
   - update MEMORY.md: current state, in progress, next steps (exact next action
     first), blockers, a session log entry; delete anything obsolete
   - register new reusable utilities, patterns and gotchas in KNOWLEDGE.md
   - run `pnpm agents:check`
6. Never commit work that fails the checks. Never push, open pull requests, create
   releases or change GitHub settings without explicit approval from the owner.

## Layout

| Path                                  | Content                                                                    |
| ------------------------------------- | -------------------------------------------------------------------------- |
| `setup.php`, `hook.php`, `plugin.xml` | GLPI plugin entry points and marketplace manifest                          |
| `src/`                                | PHP, PSR-4 `GlpiPlugin\Ticketboost\`                                       |
| `ajax/`, `front/`, `templates/`       | Endpoints, pages and Twig templates (from Phase 2)                         |
| `frontend/src/`                       | TypeScript source of the browser bundle                                    |
| `frontend/tests/`                     | Vitest unit tests                                                          |
| `public/`                             | Build output only (`js/ticketboost.js`, `css/ticketboost.css`); never edit |
| `tests/Unit`, `tests/Integration`     | PHPUnit tests (run inside the GLPI CI image)                               |
| `e2e/`                                | Playwright specs (`specs/`) and helpers (`support/`)                       |
| `locales/`                            | gettext `.pot` and `.po` files; `.mo` files are generated                  |
| `scripts/`                            | Build, CI, guard and environment scripts                                   |
| `docs/`                               | User and developer documentation (MkDocs)                                  |
| `.agents/`                            | Rules, plan, memory, knowledge, ADRs, playbooks, templates                 |

## Commands

| Command                                | Purpose                                                                  |
| -------------------------------------- | ------------------------------------------------------------------------ |
| `make help`                            | List every target                                                        |
| `make install`                         | Install Node dependencies and git hooks                                  |
| `make up` / `make down` / `make reset` | Start / stop / wipe GLPI 11 (:8011), GLPI 12 (:8012), MariaDB            |
| `make build`                           | Compile locales and build the frontend bundle                            |
| `make install-plugin`                  | Build, then install and activate the plugin in both GLPI containers      |
| `make lint`                            | ESLint, Prettier, tsc, PHP lint, php-cs-fixer, PHPStan, Rector           |
| `make test`                            | Vitest (coverage >= 90%) and PHPUnit on GLPI 11 and 12 (coverage >= 85%) |
| `make e2e`                             | Playwright against the running containers                                |
| `make check`                           | Everything CI runs except e2e                                            |
| `pnpm agents:check`                    | Rule guards: emojis, attribution, memory caps, translations, frontmatter |
| `pnpm agents:link <FILENAME>`          | Local symlink to this file for tools that expect another filename        |

Default GLPI accounts in the containers: `glpi/glpi` (super-admin), `tech/tech`,
`normal/normal`, `post-only/postonly` (self-service).

## Tool-specific instruction files

Only this file is versioned. If your tool reads a different filename, run
`pnpm agents:link <FILENAME>`: it creates a symlink to AGENTS.md and excludes it through
`.git/info/exclude`, so it never reaches git. Do not commit tool-specific files.
