# Project knowledge (max 300 lines)

Reusable building blocks and hard-won lessons. Read the utilities section before writing
any helper; register every new reusable piece here in the same session. Remove entries
that become false. Format: `path` `signature` - purpose.

## Existing utilities - USE, do not duplicate

### TypeScript (frontend/src)

- `core/safe.ts` `safe(fn, fallback, onError = report)` - run code without ever throwing into GLPI; `report(error)` logs with the `[ticketboost]` prefix.
- `boot.ts` `boot(doc): boolean` and `READY_ATTRIBUTE` (`data-ticketboost`) - plugin entry; installs Ctrl+Enter and sets `data-ticketboost="ready"` on `<html>` (e2e smoke relies on it).
- `core/dom.ts` `h(doc, tag, attrs, children)`, `replaceChildren(el, nodes)` - build DOM without HTML parsing (strings become text nodes).
- `app.ts` `startApp(doc, AppConfig, ActionHandlers): App` (`open`, `close`, `attach(doc)`, `destroy`) - wires leader, keymap and panel; Phase 2 calls it with the bootstrap payload.
- `keys/leader.ts` `createLeader(LeaderConfig, onLeader)` (`keydown`, `keyup`, `reset`), `DEFAULT_LEADER`, `KeyLike` - tap/hold state machine on timers.
- `keymap/binding.ts` `parseBinding`, `formatBinding`, `normalizeBinding`, `displayBinding`, `matchesBinding` - binding notation of ADR 0017.
- `keymap/catalog.ts` `ACTION_IDS`, `CATALOG`, `GROUP_ORDER`, `defaultKeymap()`, `mergeKeymap(base, overrides)`, `findAction(id)` - static action catalog.
- `keymap/validator.ts` `validateKeymap(keymap): KeymapIssue[]`, `usableBindings(texts)`, `RESERVED_BINDINGS` - validation and conflicts (PHP must mirror).
- `api/client.ts` `createApi(doc, baseUrl, fetcher?)` (`get`, `post`), `csrfToken(doc)`, `ApiError` - plugin endpoint client; sends `X-Requested-With` and the GLPI 11 CSRF token.
- `bootstrap.ts` `parseBootstrap(data): AppConfig | null` - validates the server payload (trust boundary); null disables the panel.
- `context/detect.ts` `detectContext(location): 'ticket' | 'other'` - ticket form is `/front/ticket.form.php?id=N` on 11 and 12.
- `boot.ts` `startPanel(doc, api)`, `PANEL_ATTRIBUTE` (`data-ticketboost-panel` = `ready` | `off`, e2e waits on it), `BOOTSTRAP_ENDPOINT`.
- `i18n/translate.ts` `createTranslator(labels)` - label lookup, falls back to the key.
- `ui/modal.ts` `createModal(doc, t)` (`open`, `push`, `close`, `isOpen`, `handleKey`), views `MenuView` / `PickerView`, `SEARCH_DELAY_MS` - the panel.
- `ui/fuzzy.ts` `fuzzyScore(query, text)`, `fuzzyFilter(query, items, text)` - accent-insensitive subsequence match.
- `ui/toast.ts` `showToast(doc, message, kind, durationMs)` - live-region toasts.
- `editor/tinymce.ts` `tinymceOf(win)`, `watchEditors(win, onDocument)` - reach every TinyMCE iframe document.
- `editor/submit.ts` `attachSubmitShortcut(doc)`, `submittableForm(target)` - Ctrl+Enter for timeline forms (ADR 0016).

### PHP (src, namespace GlpiPlugin\Ticketboost)

- `Assets` `new Assets($public_dir)->scripts()|stylesheets()` - built assets that exist, relative to `public/`; used by `plugin_init_ticketboost()`.

- `Rights` `NAME`, `canUse()`, `canConfigure()`, `fromFlags/toFlags`, `toInt(mixed)`, `exposeToSelfService()` - the `plugin_ticketboost` profile right.
- `Keymap\ActionCatalog` `ids()`, `defaultKeymap()`, `groupOf()`, `contextOf()` - action catalog (mirror of `keymap/catalog.ts`).
- `Keymap\KeymapValidator` `normalize()`, `validate()`, `RESERVED` - ADR 0017 grammar (mirror of `keymap/binding.ts`); both sides run `shared/keymap-cases.json`.
- `Keymap\KeymapResolver` `effective(admin, user)`, `issues(admin, user)`, `normalizeLayer()` - layered keymap; invalid layers are skipped.
- `Config\LeaderSettings` `fromArray()` (clamps), `toArray()`; `Config\PluginConfig` `load()`, `save()`, `fromValues()`, `toValues()` - admin defaults in `Config` context `plugin:ticketboost`.
- `Preference` (table `glpi_plugin_ticketboost_preferences`) `forUser($id)`, `saveForUser(...)` - per-user overrides, validated against the admin keymap.
- `Install\Installer` `install()`, `uninstall()` - idempotent; reinstall keeps admin choices.
- `Http\JsonResponse` `success()`, `error()`, `send()`; `Http\RequestGuard` `guard($method, $level)`, `evaluate(...)` - envelope and request checks (ADR 0018).
- `Bootstrap\ActionAvailability` `fromCapabilities()`, `forCurrentUser()`, `IMPLEMENTED`; `Bootstrap\Labels` `all()`; `Bootstrap\Payload` `build()`, `forCurrentUser()` - what `ajax/bootstrap.php` returns.
- `ProfileTab` `render()` - TicketBoost tab on the profile form; posts to `front/profile.form.php`.

### Tests and e2e

- `tests/Integration/*` extend `Glpi\Tests\DbTestCase` (`login('tech', 'tech')`, transaction per test); `InstallerTest` uses plain `TestCase` because DDL commits.
- `shared/keymap-cases.json` - cases shared by Vitest and PHPUnit; change the grammar there first.
- `e2e/support/glpi.ts` `login(page, user)` and `USERS` - log in with the default GLPI accounts (`admin`, `tech`, `normal`, `selfservice`).
- `frontend/tests/support/keys.ts` `keyLike(key, extra)`, `dispatchKey(target, type, init)`, `tapControl(target)` - fake and real key events for Vitest.

### Scripts and tools

- `scripts/agents-check.mjs` - rule guards; `--message FILE` (commit-msg hook), `--text-env VAR` (CI PR text).
- `scripts/lib/agents-check-lib.mjs` - pure checks: `findEmojis`, `findAttribution`, `checkCappedFile`, `parsePo`, `findUntranslated`, `checkFrontmatter`, `stripCommitComments`, `sha256`.
- `scripts/agents-link.mjs <FILENAME>` - local symlink to AGENTS.md, excluded via `.git/info/exclude`.
- `scripts/php-ci.sh` - the PHP gate (composer, parallel-lint, php-cs-fixer, PHPStan, Rector, DB install, PHPUnit + coverage); `STEPS=lint|test`, `DB_HOST`, `COVERAGE_MIN`.
- `tools/coverage-check.php <clover.xml> <min>` - fails below the PHP coverage minimum.
- `scripts/locales-compile.sh` - `.po` -> `.mo` plus regional copies (en_US, es_419, es_AR, es_CL, es_CO, es_EC, es_MX, es_VE).
- `scripts/build-release.sh <version>` - allowlisted runtime files -> `dist/ticketboost-<version>.tar.bz2`.
- `scripts/glpi-plugin.sh "<compose>" <services...>` - install + activate the plugin in running containers.
- `scripts/glpi-wait.sh <urls...>` - wait for GLPI to answer.
- `scripts/size-check.mjs` - 15 KB gzip budget for `public/js` + `public/css`.

## Patterns

- New user-facing string: add the msgid to `locales/ticketboost.pot`, `en_GB.po` and
  `es_ES.po` in the same commit (see `.agents/playbooks/add-translation.md`).
- New action (from Phase 2): PHP action class + registry entry + rights check + JSON
  response, TS action + keymap default, Vitest + PHPUnit + Playwright spec, docs keymap
  table (see `.agents/playbooks/add-action.md`).
- Frontend entry points and event handlers always go through `safe()`.

## GLPI gotchas

- GLPI 11+ serves only the plugin `public/` directory; hooks reference assets relative
  to it (`js/ticketboost.js`), the same pattern pluginsGLPI/fields uses.
- `plugins/<key>` folder name, plugin key and gettext domain must all be `ticketboost`;
  GLPI autoloads `GlpiPlugin\Ticketboost\` from `src/`.
- Native previous/next navigation lives in `src/CommonGLPI.php` (around line 1150-1230)
  and reads `$_SESSION['glpilistitems'][Ticket::class]`, filled by the last search.
  It binds Ctrl+Left/Right. Our circular navigation reuses the same session list.
- CSRF: a kernel listener checks every non-GET request before a legacy endpoint runs. GLPI 11
  needs the `X-Glpi-Csrf-Token` header (token from `<meta property="glpi:csrf_token">`) for
  XHR; GLPI 12 uses `Sec-Fetch-Site`/`Origin` and has no tokens (`Session::checkCSRF` is
  deprecated). Anonymous requests get GLPI's own HTML 401 before plugin code runs.
- Self-service (`helpdesk`) profiles lose every right not in `Profile::$helpdesk_rights`
  when the session loads (`Profile::cleanProfile`): add plugin rights to it (ADR 0018).
- `Session::haveRight()` returns an int in GLPI 11 and a bool in 12: never use `=== true`.
  A `(bool)` cast is removed by Rector on 12 and required by PHPStan on 11: use `Rights::has()`.
  Rector removes `(bool)` casts because the 12 stubs say bool; type the values `bool|int`.
- `DBmysql::tableExists($t)` caches per request: pass `false` as second argument after DDL.
- Plugin endpoints are served at `/plugins/ticketboost/ajax/x.php` and the bundle at
  `/plugins/ticketboost/js/ticketboost.js`, so `new URL('../ajax/', import.meta.url)` works.
- `thecodingmachine/safe` ships with GLPI: PHPStan requires `use function Safe\preg_match`
  (and `json_decode`, `json_encode`); `http_response_code()` is forbidden, use
  `header('...', true, $status)`.
- A class `GlpiPlugin\Ticketboost\Preference` maps to `glpi_plugin_ticketboost_preferences`;
  do not redeclare `$rightname` (typed in GLPI 12, untyped in 11).
- GLPI native shortcuts to avoid: Ctrl+Alt+G (fuzzy search), Ctrl+Alt+E (entity selector),
  Ctrl+Left/Right (item navigation).
- TinyMCE editors live in iframes: keyboard events do not bubble to the parent document;
  listeners must be attached inside each editor too.
- GLPI already sends forms on Ctrl+Enter inside TinyMCE (`editor.addShortcut('ctrl+13')`
  in `src/Html.php`) and in `#page form textarea` (`public/js/common.js`), both through
  `submitparentForm()`: never add a second handler there (ADR 0016).
- Timeline answer blocks: `#new-ITILFollowup-block`, `#new-TicketTask-block` (Bootstrap
  collapse, form lazy-loaded on first open in 12); forms post to `itilfollowup.form.php` /
  `tickettask.form.php` with one submit button `add` (new) or `update` (edit).
- Plugin PHP tooling (PHPStan, Rector, php-cs-fixer, PHPUnit bootstrap) depends on a GLPI
  development checkout at `../../`; it only runs inside the official CI image
  `ghcr.io/glpi-project/githubactions-glpi-apache:php-X-glpi-Y`.
- GLPI 12 requires PHP >= 8.3; GLPI 11 supports PHP 8.2 to 8.5.
- Languages declared by GLPI 11/12: en_GB, en_US, es_ES, es_419, es_AR, es_CL, es_CO,
  es_EC, es_MX, es_VE (`src/Glpi/Locale/LanguageRegistry.php` in 12).
- Official runtime image `glpi/glpi:<version>` auto-installs when the five `GLPI_DB_*`
  variables are set; default accounts: glpi/glpi, tech/tech, normal/normal,
  post-only/postonly. GLPI lives in `/var/www/glpi`, runs as www-data.
- GLPI core itself uses Playwright for e2e (`tests/e2e` in glpi-project/glpi): its page
  objects are a good reference for selectors.
- PHPStan level max with the safe-rule extension flags `define()`: annotate each with
  `@phpstan-ignore theCodingMachineSafe.function` (same as the official template). Assign
  whole arrays to `$PLUGIN_HOOKS[...]['ticketboost']` to avoid offset-on-mixed errors.
- Rector baseline prefers `final readonly class` and `__DIR__ . '/../x'` over `dirname(__DIR__)`.
- GLPI `tools:licence_headers_check` scans every file type, including build outputs; the
  project checks PHP headers itself in `scripts/php-ci.sh`.
- `ghcr.io/glpi-project/githubactions-mariadb` keeps data in /dev/shm: it needs
  `shm_size: 1g` (compose) or `--shm-size=1g`, otherwise InnoDB fails with error 28.
- The plugin works mounted read-only: GLPI never writes into `plugins/ticketboost`.
- No runtime Composer dependencies: GLPI autoloads `src/`, so release archives ship no
  `vendor/`. Adding a runtime dependency requires `composer install --no-dev` in the
  release job and adding vendor to the `scripts/build-release.sh` allowlist.

## Testing learnings

- Apps started in a test listen on `document`: call `app.destroy()` in `afterEach`, or
  later tests see several panels. `toHaveBeenCalledWith(doc)` deep-compares documents
  (two empty ones are equal): compare `mock.calls` with `toBe`.
- happy-dom: `vi.advanceTimersByTimeAsync(0)` flushes promise chains under fake timers.
- Guard tests must not contain forbidden names or emojis: inject custom hashes
  (`sha256('acmebot')`) and write emojis as `\u{...}` escapes.
- Vitest runs `frontend/tests/**` and `scripts/tests/**`; coverage thresholds apply to
  `frontend/src/**` only (`main.ts` excluded because it just calls `boot`).
- Playwright WebKit does not run on Arch hosts (missing Ubuntu libs such as libicu74):
  use `make e2e-container ARGS="--project=glpi12-webkit"` (official Playwright image,
  host network). Chromium and Firefox run on the host.
- The smoke spec proves install + activation + bundle loading on both interfaces by
  checking `<html data-ticketboost="ready">`.

## Tooling learnings

- TypeScript 7 is not supported by typescript-eslint 8 (peer `<6.1`): TS is pinned to `~6.0`
  and `.github/dependabot.yml` ignores TS major updates; drop both once supported.
- pnpm 12 blocks dependency build scripts unless approved in `pnpm-workspace.yaml`
  `allowBuilds`.
- Vite library mode: `fileName: () => 'js/ticketboost.js'` and
  `cssFileName: 'css/ticketboost'` place outputs directly under `public/`.
- Never write the two-word phrase that tools put in footers ("generated" followed by
  "with") in docs: the attribution guard rejects it. Use "tool-generated".
- release-please's default pull request header (robot shortcode, "beep boop") and footer
  (the footer phrase above) break the emoji and attribution rules. Keep the neutral
  `pull-request-header` / `pull-request-footer` in `release-please-config.json`. Before
  enabling any tool that writes commits, PRs or releases, check its default texts against
  RULES.md and override them before its first run.
- `make fix-php` applies Rector and php-cs-fixer in the container; run it before `make lint-php`.
  `make test-php` stops at the first failing GLPI version: run `tools11` and `tools12` separately to see both.
- zsh does not word-split unquoted variables: wrap multi-command loops in `bash -c`.
- Rootless podman cannot bind ports below 1024: GLPI runs on 8011 (11) and 8012 (12).
- Writing `\u` escapes through the file editor stores the literal characters; use ASCII
  regex classes (`/\p{M}/gu`) or write the escape from a script.
- The bundle size check measures only what `main.ts` imports; until Phase 2 wires
  `startApp`, measure the full app with a temporary entry (about 6.5 KB gzip in Phase 1).
- Prettier must not format `docs/`: it breaks MkDocs admonition indentation.
- Running the GLPI CI image as root (rootless podman) needs `--allow-superuser` for the
  console; `scripts/php-ci.sh` adds it automatically.
