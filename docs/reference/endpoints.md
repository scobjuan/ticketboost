# Endpoints

TicketBoost does not use the GLPI REST API. The browser bundle calls the plugin endpoints
below with the user session. Every request is authorized again on the server.

| Endpoint             | Method | Status  | Purpose                                                                   |
| -------------------- | ------ | ------- | ------------------------------------------------------------------------- |
| `ajax/bootstrap.php` | GET    | Ready   | Effective leader, keymap, available actions and translated labels         |
| `ajax/action.php`    | POST   | Planned | Runs an action on a ticket                                                |
| `ajax/navigate.php`  | GET    | Ready   | Address to open for next, previous, list or my tickets                    |
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

## Navigation

`ajax/navigate.php?to=<target>[&id=<ticket>]` computes the address to open and answers it
in `redirect`; the browser does the move and GLPI checks the right on the ticket when the
page loads. It is read-only, so it needs no CSRF token.

| `to`         | `id`     | Result                                                                  |
| ------------ | -------- | ----------------------------------------------------------------------- |
| `next`       | required | The ticket after `id`; after the last one, the first                    |
| `previous`   | required | The ticket before `id`; before the first one, the last                  |
| `list`       | optional | The ticket list, with the last search restored by GLPI                  |
| `my_tickets` | optional | The ticket list filtered to the tickets assigned to the user (technician) or requested by the user (self-service) |

Any other `to` value, an unknown parameter or an `id` that is not a positive integer is
answered with 400. When there is no other ticket the answer is 404 with a translated
message. Tickets the user cannot open are skipped.
