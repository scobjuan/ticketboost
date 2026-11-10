# Architecture

TicketBoost has two halves: a TypeScript bundle that handles the keyboard and the panel,
and PHP endpoints that perform the actions through native GLPI classes.

```text
Browser (every GLPI page)                         GLPI server
+---------------------------------+               +--------------------------------------+
| public/js/ticketboost.js        |  bootstrap    | ajax/bootstrap.php                   |
|  keys/   leader detection       | ------------> |  rights-filtered actions, keymap,    |
|  keymap/ bindings, conflicts    |               |  translated labels                   |
|  ui/     panel, pickers, toasts |  action       | ajax/action.php                      |
|  actions/ client side of actions| ------------> |  RequestGuard: session, CSRF, rights |
|  editor/ TinyMCE bridge         |               |  src/Action/*: Ticket, Ticket_User,  |
|  api/    fetch + CSRF           |  navigate     |  Group_Ticket, ITILSolution, ...     |
+---------------------------------+ ------------> | ajax/navigate.php (glpilistitems)    |
                                                  +--------------------------------------+
```

## Principles

- **Native classes only.** Actions call `CommonDBTM` methods on GLPI classes, so rights,
  business rules, history and notifications behave exactly as in the GLPI interface.
- **Server-side authorization.** The browser only decides what to display; every request
  is checked again on the server.
- **Never break GLPI.** Every frontend entry point and handler runs through `safe()`; a
  plugin error is logged with the `[ticketboost]` prefix and the page keeps working.
- **No hardcoded text in TypeScript.** Labels come from PHP gettext.
- **Small bundle.** Zero runtime dependencies, 15 KB gzip budget enforced in CI.

## Repository layout

| Path                                | Content                                           |
| ----------------------------------- | ------------------------------------------------- |
| `setup.php`, `hook.php`             | Plugin registration, hooks, install and uninstall |
| `plugin.xml`                        | Marketplace manifest                              |
| `src/`                              | PHP classes, namespace `GlpiPlugin\Ticketboost`   |
| `ajax/`, `front/`, `templates/`     | Endpoints, pages, Twig templates                  |
| `frontend/src/`                     | TypeScript source; built into `public/`           |
| `locales/`                          | gettext sources (`.pot`, `.po`)                   |
| `tests/`, `frontend/tests/`, `e2e/` | PHPUnit, Vitest and Playwright tests              |

Design decisions are recorded as ADRs in
[`.agents/decisions/`](https://github.com/scobjuan/ticketboost/tree/main/.agents/decisions).
