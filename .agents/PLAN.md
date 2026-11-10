# TicketBoost - Master Plan

> Source of truth for scope, specification, decisions and roadmap. Approved by the owner
> on 2026-11-09 after a structured planning interview. Live progress is tracked in
> `.agents/MEMORY.md`; decisions are recorded one per file in `.agents/decisions/`.
> Change this plan only with owner approval, and record the change in an ADR.

## 1. Context

Handling tickets in GLPI takes too many clicks: leave the ticket, assign someone, change
status, close, open the next ticket. TicketBoost is an open-source GLPI plugin that maps
these actions to keyboard shortcuts behind a leader key and a small "which-key" style
modal, so a technician can process a queue without the mouse. It must work on any GLPI
11/12 install, for admins and non-admins, in English and Spanish.

Deliverables: the plugin, unit + e2e tests that gate every merge, GitHub Actions CI/CD
with SemVer releases, user/admin/developer docs, and an agent-agnostic AI workflow setup
so any coding agent can resume where the previous session stopped.

Owner / sole author: Juan A. Escobar <scobjuan@gmail.com> (GitHub: scobjuan).
Repo: github.com/scobjuan/ticketboost (exists, empty, private). Local: /home/juescobar/projects/ticketboost.

## 2. Decisions (resolved in the planning interview, 2026-11-09)

| Topic          | Decision                                                                                                                                                                                    |
| -------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| GLPI support   | 11.x and 12.x in one release line (`min 11.0.0`, `max 12.0.99`). 10.x is EOL (10.0.28). PHP >= 8.2 (GLPI 12 requires PHP >= 8.3)                                                            |
| Execution      | Plugin-owned AJAX endpoints (CSRF) calling native GLPI classes. No REST API dependency, no DOM-click automation                                                                             |
| Leader         | Tap-and-release of Ctrl (no other key pressed in between) opens the modal. Ctrl+C/V/etc. never open it and keep working                                                                     |
| Leader config  | Key (Control/Alt/Shift/Meta), tap count 1-3, tap window ms, or "hold N ms" mode. Admin default + user override                                                                              |
| After leader   | Modal stays open; plain single keys trigger actions; submenus for status/priority/pickers; Esc closes                                                                                       |
| Next/previous  | Circular over GLPI session list `$_SESSION['glpilistitems']['Ticket']` (last -> first, first -> last). Fallback: "my unresolved tickets" by priority desc, date asc, with a discreet notice |
| Interfaces     | Central and self-service; every action filtered by the user's native rights                                                                                                                 |
| Customization  | Admin defaults + per-profile enablement right; per-user remap/disable with conflict detection                                                                                               |
| Close action   | Admin-configurable mode: `quick_solution` (inline solution text or template -> ITILSolution, GLPI's normal flow) or `direct_close` (status Closed when rights allow)                        |
| Name           | Display "TicketBoost", key `ticketboost`, namespace `GlpiPlugin\Ticketboost`. Tagline: "Keyboard shortcuts for faster ticket handling in GLPI"                                              |
| License        | GPLv3+                                                                                                                                                                                      |
| Frontend       | TypeScript strict, no framework, Vite -> single ES module + prefixed CSS, zero runtime deps, budget < 15 KB gzip                                                                            |
| Tests          | PHPUnit (GLPI test bootstrap), Vitest + happy-dom, Playwright (chromium, firefox, webkit)                                                                                                   |
| Coverage gates | PHP >= 85% lines, TS >= 90% lines/branches. Blocking                                                                                                                                        |
| Local env      | Everything in containers: podman-compose (GLPI 11, GLPI 12, MariaDB, PHP tools) + Makefile. Owner installs `podman-compose`                                                                 |
| Git flow       | Trunk-based: protected `main`, short branches, squash-merge PRs, required checks                                                                                                            |
| Releases       | release-please with owner PAT (authorship = owner) -> CHANGELOG, SemVer tag, GitHub Release with installable archive                                                                        |
| Docs           | Markdown in `docs/`, MkDocs Material, deployed to GitHub Pages on release                                                                                                                   |
| Profiles       | All profiles enabled on install; admin can disable per profile                                                                                                                              |
| Agent files    | Only `AGENTS.md` is versioned. Tool-specific filenames are local symlinks excluded via `.git/info/exclude`                                                                                  |
| AI extras      | Playbooks, ADRs, automated guards (`agents:check`), session log in MEMORY                                                                                                                   |
| First session  | Phase 0 only, then stop for owner review. Nothing is pushed without approval                                                                                                                |

### Non-negotiable rules (from the owner)

1. No emojis anywhere, except where Conventional Commits contemplate them in commit messages.
2. Everything (code, docs, commits, PRs, merges, releases) is authored by the developer only.
   No `Co-Authored-By` trailers, no tool-generated footers, no agent or tool names anywhere.
   This overrides any default attribution behavior of the tooling.
3. English for code, docs, commits, PRs, agent files. User-facing plugin strings ship in
   English and Spanish (follows the GLPI user's language).
4. Conventional Commits + Semantic Versioning.

## 3. Product specification

### 3.1 Leader detection (TS `frontend/src/keys/leader.ts`)

- Default: key `Control`, taps `1`, `tapMaxMs` 300 (press-to-release), `tapWindowMs` 400 (between taps), mode `tap`.
- Algorithm: on keydown of the leader key (non-repeat) start a candidate; any other keydown
  while held cancels it (this is why Ctrl+C never triggers); on keyup within `tapMaxMs`
  count a tap; when the tap count is reached within `tapWindowMs` -> open modal.
- Mode `hold`: open after `holdMs` with the key held and no other key pressed.
- Ignore events with `isComposing` (IME). Use `event.key` for the leader and `event.code`
  for action keys, so Option-modified characters on macOS and non-QWERTY layouts behave.
- Listeners in capture phase on `document` AND inside every TinyMCE iframe (TinyMCE
  editors live in iframes; key events do not bubble to the parent document).
- While the modal is open: bound keys get `preventDefault()` + `stopPropagation()`.
  Browser-reserved chords (Ctrl+W/T/N/Tab, Ctrl+Shift+T/N/W) cannot be intercepted; the
  keymap validator rejects them. GLPI native chords to avoid: Ctrl+Alt+G (fuzzy search),
  Ctrl+Alt+E (entity selector), Ctrl+Left/Right (prev/next item in `src/CommonGLPI.php`).

### 3.2 Modal UX (TS `frontend/src/ui/`)

- Compact panel (about 360px wide, bottom-center), minimal and modern, uses GLPI/Tabler
  CSS variables (`--tblr-*`) so it follows light/dark themes; all classes prefixed `tb-`.
- Lists only the actions available in the current context and for the current rights:
  key badge + label, grouped (Navigate, Assign, Ticket). Submenus show a breadcrumb.
- Pickers (assign other, unassign others) embed a fuzzy search input inside the modal;
  arrow keys + Enter; multi-select for unassign others.
- Footer hint: `?` all shortcuts, `Esc` close. Toast for results and errors.
- Accessibility: `role="dialog"`, `aria-modal`, focus trap, focus restored on close,
  `prefers-reduced-motion` respected, axe checks in e2e.
- Fault tolerance: every handler wrapped; a plugin error never breaks the GLPI page.

### 3.3 Default keymap (after the leader; all remappable)

| Key                | Action                                                     | Context     | Self-service |
| ------------------ | ---------------------------------------------------------- | ----------- | ------------ |
| `N` / `ArrowRight` | Next ticket (circular)                                     | ticket form | yes          |
| `P` / `ArrowLeft`  | Previous ticket (circular)                                 | ticket form | yes          |
| `L`                | Back to list (last search)                                 | ticket form | yes          |
| `M`                | My tickets (assigned to me; self-service: requested by me) | anywhere    | yes          |
| `A`                | Assign to me                                               | ticket form | no           |
| `O`                | Assign to another technician or group (picker)             | ticket form | no           |
| `U`                | Unassign me                                                | ticket form | no           |
| `X`                | Unassign other users (multi picker of current assignees)   | ticket form | no           |
| `S`                | Change status (submenu, digits 1-n)                        | ticket form | no           |
| `C`                | Close (admin mode: quick solution or direct close)         | ticket form | no           |
| `F`                | Open followup editor focused                               | ticket form | yes          |
| `T`                | Open task editor focused                                   | ticket form | no           |
| `R`                | Change priority (submenu 1-6)                              | ticket form | no           |
| `?`                | Show all shortcuts                                         | anywhere    | yes          |

- `Ctrl+Enter` (direct, no leader) submits the focused followup/task form via its own submit button.
- "no" for self-service means hidden unless the profile actually has the right.
- MVP scope is exactly this list. Anything else (create ticket, solve-and-next, urgency) is out of scope unless the owner adds it.

### 3.4 Server actions (PHP, each re-checks rights server-side)

| Action             | GLPI implementation                                                                               | Right checks                                        |
| ------------------ | ------------------------------------------------------------------------------------------------- | --------------------------------------------------- |
| assign_me          | `Ticket_User` add type ASSIGN for current user                                                    | `Ticket::ASSIGN` or `Ticket::OWN` + `canUpdateItem` |
| assign_other       | `Ticket_User` / `Group_Ticket` add ASSIGN                                                         | `Ticket::ASSIGN`                                    |
| unassign_me        | delete own `Ticket_User` ASSIGN link                                                              | `canUpdateItem`                                     |
| unassign_others    | delete selected `Ticket_User`/`Group_Ticket` ASSIGN links                                         | `Ticket::ASSIGN`                                    |
| change_status      | `Ticket::update(['status' => n])`, only statuses allowed by `Ticket::getAllowedStatusArray`       | `canUpdateItem`                                     |
| close              | `quick_solution`: `ITILSolution::add` (text or `SolutionTemplate`); `direct_close`: status CLOSED | solution rights / `Ticket::isAllowedStatus`         |
| change_priority    | `Ticket::update(['priority' => n])`                                                               | `Ticket::CHANGEPRIORITY`                            |
| navigate           | compute next/prev/list URL from `glpilistitems` with wrap + fallback query                        | `Ticket::canView`, per item `canViewItem`           |
| search_assignables | users with `own_ticket` right in ticket entity; groups with `is_assign`                           | `Ticket::ASSIGN`                                    |

- Use GLPI APIs only (CommonDBTM, `DBmysqlIterator`); no raw SQL. All responses JSON
  `{ok, message, data, redirect?}` with translated messages.

### 3.5 Configuration and storage

- Admin defaults: `Config::setConfigurationValues('plugin:ticketboost', ...)` (leader, keymap, close mode).
- Rights: `plugin_ticketboost` with READ (use) and UPDATE (configure); tab on Profile form.
  Install grants READ to all profiles and READ+UPDATE to profiles with `config` UPDATE.
- User preferences table `glpi_plugin_ticketboost_preferences` (users_id unique, enabled,
  leader JSON, keymap JSON, date_mod). Tab on Preference/User pages with a key recorder
  and live conflict detection (shared rules implemented in TS and PHP, both tested).
- Bootstrap payload (rights-filtered catalog, effective keymap, translated labels, CSRF
  handling) served by `ajax/bootstrap.php` and cached per session.

### 3.6 i18n

- Gettext domain `ticketboost`: `locales/ticketboost.pot`, `en_GB.po`, `es_ES.po`; `.mo` built.
- Build copies `en_GB` -> `en_US` and `es_ES` -> every other `es_*` locale GLPI declares
  (list resolved from `$CFG_GLPI['languages']` in Phase 0) so any Spanish variant gets Spanish.
- Frontend never hardcodes text: labels arrive in the bootstrap payload via `__()`.
- `agents:check` fails if a msgid lacks an `es_ES` translation.

## 4. Architecture and repository layout

```
ticketboost/
  setup.php  hook.php  plugin.xml  composer.json  package.json  Makefile  compose.yaml
  ajax/                 bootstrap.php, context.php, action.php, navigate.php, search.php
  front/                config.form.php (admin), preference.form.php
  src/                  PHP, PSR-4 GlpiPlugin\Ticketboost\
    Action/             ActionInterface, ActionRegistry, AssignMe, AssignOther, UnassignMe,
                        UnassignOthers, ChangeStatus, Close, ChangePriority
    Navigation/         TicketNavigator (wrap + fallback)
    Keymap/             Keymap, KeymapValidator, ConflictDetector, DefaultKeymap
    Config/             PluginConfig, UserPreference, ProfileRight
    Http/               JsonResponse, RequestGuard (CSRF/session/rights)
    Install/            Installer, Migration steps per version
  templates/            Twig for config/preference tabs
  frontend/src/         TS: keys/, keymap/, ui/, actions/, api/, editor/, context/, i18n/
  public/               build output only: js/ticketboost.js, css/ticketboost.css
  locales/              .pot, .po, generated .mo
  tests/                PHPUnit: Unit/ (pure), Integration/ (DbTestCase), bootstrap.php
  frontend/tests/       Vitest specs
  e2e/                  Playwright: specs/, support/ (helpers), seed/ (from Phase 3)
  docker/               database init for the local environment
  docs/                 MkDocs source
  scripts/              agents-check.mjs, agents-link.mjs, php-ci.sh, build-release.sh, locales-compile.sh
  tools/                HEADER (license header), coverage-check.php
  .agents/              AI workflow files (section 7)
  .github/              workflows/, PULL_REQUEST_TEMPLATE.md, ISSUE_TEMPLATE/, dependabot.yml
```

- Hooks in `setup.php`: `csrf_compliant`, `add_javascript_module` (bundle), `add_css`,
  `config_page`, `Plugin::registerClass` for Profile/Preference tabs. Assets referenced
  relative to `public/` (pattern used by pluginsGLPI/fields). Legacy `ajax/` + `front/` dirs
  are still supported by GLPI 11/12 (fields and datainjection use them).
- Start from the official template structure `github.com/pluginsGLPI/empty`
  (phpstan.neon, rector.php, .php-cs-fixer.php, phpunit.xml, tests/bootstrap.php).

## 5. Quality and testing (blocking for every merge)

- PHP unit: pure classes (Keymap, validator, navigator math) without DB.
- PHP integration: `DbTestCase` against a real GLPI DB in the tools container; each action
  tested for success, missing right, wrong entity, invalid input.
- TS unit: leader state machine with fake timers, keymap matching, conflicts, modal
  rendering, picker filtering, API client errors, TinyMCE bridge (mocked).
- E2E (Playwright) against seeded GLPI 11 and 12: seed users `tech1`, `tech2`, `selfservice`,
  `admin`, groups, about 6 tickets. Specs: leader tap vs Ctrl+C, each MVP action, wrap
  navigation, fallback list, both close modes, rights filtering in self-service, user
  remap + conflicts, Spanish UI, axe accessibility. Traces/video on failure.
- Static: PHPStan (template level), php-cs-fixer, rector dry-run, ESLint + Prettier,
  `tsc --noEmit`, stylelint, bundle size budget, MkDocs `--strict`.
- Local hooks (husky): pre-commit lint-staged + `agents:check`; commit-msg commitlint +
  attribution guard; pre-push TS unit tests. CI is the real gate.

## 6. CI/CD (GitHub Actions)

- `ci.yml` (PR + push main): conventions (commitlint on commits and PR title, agents-check
  on tree and PR title/body), frontend (lint, format, typecheck, Vitest coverage >= 90%,
  build, bundle budget), php matrix (GLPI 11.0.x on PHP 8.2 and 8.5, GLPI 12.0.x on PHP 8.3
  and 8.5, coverage >= 85%), e2e (GLPI 11 and 12 containers; chromium on both, firefox and
  webkit on 12), docs build (strict), workflow lint (actionlint), release archive dry run.
- The php job runs `scripts/php-ci.sh` inside the official image
  `ghcr.io/glpi-project/githubactions-glpi-apache:php-X-glpi-Y` (same script as
  `make test-php`). The reusable `glpi-project/plugin-ci-workflows` was evaluated and not
  used: it requires a manual CHANGELOG edit in every PR (conflicts with release-please) and
  does not enforce coverage thresholds (ADR 0009).
- `release.yml` (push main): release-please with secret `RELEASE_PLEASE_TOKEN` (owner PAT).
  Version markers in `setup.php` and `plugin.xml` updated as extra-files. On release:
  `scripts/build-release.sh` (composer --no-dev, pnpm build, msgfmt, export-ignore dev files)
  -> `ticketboost-X.Y.Z.tar.bz2` attached to the release.
- `docs.yml`: build on PR, deploy to GitHub Pages on release.
- Dependabot for composer, npm, actions (conventional prefixes `chore(deps)`).
- Branch protection on `main` (applied by owner or with explicit approval): PR required,
  all checks required, linear history, no force push, 0 approvals (solo maintainer).
- Versioning: start at 0.1.0 (`Release-As`), `bump-minor-pre-major`; 1.0.0 when stable.
- Commit scopes: `keys`, `ui`, `actions`, `nav`, `config`, `i18n`, `install`, `api`, `e2e`,
  `ci`, `docs`, `agents`, `deps`, `release`.

## 7. AI workflow architecture (agent-agnostic)

```
AGENTS.md                   Entry point: project summary, layout, commands, MANDATORY session protocol
.agents/
  RULES.md                  Binding rules (owner rules + code/security/quality/docs rules)
  PLAN.md                   This master plan (scope, specs, phases, exit criteria)
  MEMORY.md                 Live state, capped (see below)
  KNOWLEDGE.md              Reusable utilities registry + patterns + gotchas, capped
  decisions/NNNN-title.md   ADRs (context, options, decision, consequences); 0001..00NN seeded from section 2
  playbooks/                start-session.md, end-session.md, add-action.md, add-translation.md,
                            release.md, debug-e2e.md, add-glpi-version.md
  templates/                adr.md, playbook.md, session-entry.md
scripts/agents-check.mjs    Guards (below)
scripts/agents-link.mjs     `pnpm agents:link <FILENAME>` creates a local symlink to AGENTS.md
                            under the filename a given tool expects and adds it to .git/info/exclude
docs/                       Human documentation (MkDocs)
README.md CONTRIBUTING.md SECURITY.md CODE_OF_CONDUCT.md LICENSE CHANGELOG.md
```

Session protocol (in AGENTS.md):

1. Start: read RULES -> MEMORY -> PLAN (current phase section) -> KNOWLEDGE utilities.
2. Before writing a helper: search KNOWLEDGE "Existing utilities"; reuse or extend.
3. Architecture change -> new ADR. Repeatable task -> follow (or create) a playbook.
4. End: update MEMORY (state, in progress, next step first, session log entry, prune),
   register new utilities/gotchas in KNOWLEDGE, run `agents:check`.
5. Never commit failing checks; never push or open PRs without owner approval.

MEMORY.md sections (caps enforced): Header (updated date, version, current phase) |
Current state (20) | In progress, with branch (10) | Next steps, exact next action first (20) |
Blockers / waiting on owner (10) | Open questions (10) | Environment status: tools installed,
secret NAMES configured, GitHub settings applied (10) | Session log, last 5 sessions (date,
done, stopped at, next) | Links to ADRs. Total <= 250 lines.

KNOWLEDGE.md sections: Existing utilities - USE, do not duplicate (PHP / TS / tests /
scripts, with path + signature + one-line purpose) | Patterns (how an action is built end to
end) | GLPI gotchas (glpilistitems, TinyMCE iframes, CSRF, public/ assets, rights constants,
11 vs 12 differences) | Testing learnings | Tooling learnings. Total <= 300 lines.

`agents:check` guards (pre-commit + CI): MEMORY/KNOWLEDGE line and section caps; emoji scan
(Unicode Extended_Pictographic) over tracked files; attribution scan over files, commit
messages, PR title/body: attribution trailers and footers, and tool names matched by
SHA-256 of lowercase tokens so the repo never contains the names in plain text; every msgid
translated in es_ES; required frontmatter on ADRs and playbooks.

## 8. Roadmap (each phase ends with green checks, updated MEMORY, owner review)

- **Phase 0 - Foundation.** See section 9. Stop for owner review after it.
- **Phase 1 - Keys engine and modal (TS).** Leader state machine, keymap model/validator/
  conflicts, modal + submenus + picker shell, help overlay, TinyMCE bridge, Ctrl+Enter,
  static catalog stub. Exit: TS coverage >= 90%, bundle < 15 KB gzip.
- **Phase 2 - Backend core.** Installer/migrations, rights + Profile tab, admin config,
  user preferences table, bootstrap/context endpoints, RequestGuard, i18n plumbing.
  Exit: PHP coverage >= 85%, plugin installs/uninstalls cleanly on 11 and 12.
- **Phase 3 - Navigation.** next/prev circular, fallback list, back to list, my tickets. E2E.
- **Phase 4 - Assignment.** assign me, assign other (picker), unassign me, unassign others. E2E.
- **Phase 5 - Ticket actions.** change status, close (both modes), change priority,
  followup/task editor focus + Ctrl+Enter. E2E.
- **Phase 6 - Configuration UI.** Admin page, user preference tab with key recorder and
  conflict detection, leader settings (taps, window, hold). E2E incl. Spanish UI.
- **Phase 7 - Hardening and 0.1.0.** Accessibility (axe), cross-browser, security review
  of endpoints, docs complete with screenshots, marketplace metadata, release 0.1.0.
  Owner makes the repo public and submits to the GLPI marketplace.

## 9. Phase 0 tasks

1. `mkdir /home/juescobar/projects/ticketboost`, `git init -b main`, set local
   `user.name "Juan A. Escobar"` / `user.email scobjuan@gmail.com`, remote
   `git@github.com:scobjuan/ticketboost.git` (no push).
2. Scaffold from the pluginsGLPI/empty layout: `setup.php` (min 11.0.0, max 12.0.99,
   version markers), `hook.php` (install/uninstall stubs), `plugin.xml` (en/es descriptions,
   tags, GPLv3), `composer.json` (php >= 8.2, PSR-4, dev tools), PHPStan, rector,
   php-cs-fixer, phpunit.xml, tests bootstrap, LICENSE (GPLv3 text).
3. Frontend skeleton: `package.json` (pnpm), TS strict, Vite lib build to `public/`,
   ESLint + Prettier, Vitest + coverage thresholds, one smoke unit test, size budget.
4. Container env: `compose.yaml` (glpi11, glpi12, mariadb, tools), `docker/`, `Makefile`
   (`up`, `down`, `install-plugin`, `seed`, `test`, `test-php`, `test-ts`, `e2e`, `lint`,
   `check`, `build`, `release-dry`), seed script stub.
5. Playwright config + one smoke e2e: plugin installed and enabled on GLPI 11 and 12, bundle loaded.
6. Locales: `.pot`, `en_GB.po`, `es_ES.po` with the plugin name/description; msgfmt in tools image.
7. AI setup: `AGENTS.md`, `.agents/RULES.md`, `.agents/PLAN.md` (this plan), `.agents/MEMORY.md`,
   `.agents/KNOWLEDGE.md`, ADRs 0001-00NN from section 2, playbooks, templates,
   `scripts/agents-check.mjs`, `scripts/agents-link.mjs`. Run the link script locally for the
   current agent's expected filename (excluded, never committed).
8. Docs: `mkdocs.yml` + `docs/` skeleton (index, getting-started, user guide, admin guide,
   configuration, keymap reference, architecture, development, testing, contributing, FAQ);
   `README.md`, `CONTRIBUTING.md`, `SECURITY.md`, `CODE_OF_CONDUCT.md`.
9. Git tooling: husky, lint-staged, commitlint (conventional), attribution guard in commit-msg.
10. CI/CD: `ci.yml`, `release.yml`, `docs.yml`, release-please config + manifest (0.1.0
    target), dependabot, PR and issue templates. Validate YAML with actionlint (container).
11. Commit locally in small conventional commits authored by the owner, with NO attribution
    trailers. Stop and report to the owner. Do not push, do not change GitHub settings.

## 10. Verification (Phase 0 exit)

- `make up` brings GLPI 11 and 12 + MariaDB up with podman-compose; `make install-plugin`
  installs and activates `ticketboost` on both (`bin/console plugin:install/activate`).
- `make check` passes: lint-php, lint-ts, typecheck, unit-ts with coverage, unit-php,
  `agents:check`, docs build `--strict`.
- `make e2e` smoke spec green on GLPI 11 and 12 (chromium at least locally).
- `act`-free check: `actionlint` passes on all workflows.
- `git log --format='%an <%ae>%n%b'` shows only the owner and no trailers;
  `node scripts/agents-check.mjs` reports zero emoji/attribution findings.
- Prerequisite for the owner before execution: `sudo pacman -S podman-compose`.
