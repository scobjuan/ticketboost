# Endpoints

TicketBoost does not use the GLPI REST API. The browser bundle calls the plugin endpoints
below with the user session. Every request is authorized again on the server.

| Endpoint             | Method | Status  | Purpose                                                                   |
| -------------------- | ------ | ------- | ------------------------------------------------------------------------- |
| `ajax/bootstrap.php` | GET    | Ready   | Effective leader, keymap, available actions and translated labels         |
| `ajax/action.php`    | POST   | Planned | Runs an action on a ticket                                                |
| `ajax/navigate.php`  | GET    | Planned | Next, previous or list URL                                                |
| `ajax/search.php`    | GET    | Planned | Technicians and groups that can be assigned                               |

Responses are JSON: `{"ok": true, "message": "...", "data": {}, "redirect": "..."}`.
They are never cached.

## Request rules

Every endpoint checks, in this order, and answers with the status shown:

| Check                                        | Status |
| -------------------------------------------- | ------ |
| The user has a session                       | 401    |
| The HTTP method is the expected one          | 405    |
| The `X-Requested-With: XMLHttpRequest` header is present | 400 |
| The profile has the TicketBoost right        | 403    |

`ajax/bootstrap.php` only needs a session: a user without the right receives
`{"enabled": false}` and the shortcuts stay off.

CSRF protection is enforced by GLPI itself before the endpoint runs: GLPI 11 requires the
session token in the `X-Glpi-Csrf-Token` header on POST requests, and GLPI 12 validates
`Sec-Fetch-Site` and `Origin`. The bundle sends what each version needs.

## Bootstrap payload

```json
{
  "ok": true,
  "message": "",
  "data": {
    "enabled": true,
    "leader": { "key": "Control", "mode": "tap", "taps": 1, "tapMaxMs": 300, "tapWindowMs": 400, "holdMs": 500 },
    "keymap": { "next": ["KeyN", "ArrowRight"], "help": ["?"] },
    "available": ["help"],
    "labels": { "ui.title": "TicketBoost" },
    "options": {}
  }
}
```

`keymap` is the effective keymap: defaults, then the administrator overrides, then the
user overrides. A layer that would leave the keymap with an invalid or conflicting
binding is ignored as a whole. `available` only lists actions that exist in the installed
version and that the profile may use; the page decides which of them apply to it.
