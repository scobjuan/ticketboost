# Endpoints

TicketBoost does not use the GLPI REST API. The browser bundle calls the plugin endpoints
below with the user session. Every request is authorized again on the server.

| Endpoint             | Method | Status  | Purpose                                                                   |
| -------------------- | ------ | ------- | ------------------------------------------------------------------------- |
| `ajax/bootstrap.php` | GET    | Ready   | Effective leader, keymap, available actions and translated labels         |
| `ajax/action.php`    | POST   | Ready   | Runs an assignment or ticket action (status, priority, close)             |
| `ajax/navigate.php`  | GET    | Ready   | Address to open for next, previous, list or my tickets                    |
| `ajax/search.php`    | GET    | Ready   | Users and groups that can be assigned, or the current assignees           |

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

## Settings pages

The settings are ordinary GLPI forms, not JSON endpoints. GLPI checks the CSRF protection
of every POST before the page runs.

| Page                         | Who may use it                                                      |
| ---------------------------- | ------------------------------------------------------------------- |
| `front/config.form.php`      | The configure right of the plugin                                   |
| `front/profile.form.php`     | The right to update profiles                                        |
| `front/preference.form.php`  | Own settings: the use right. Another user (`users_id`): configure right and the right to update that user, who may not have more rights |

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

## Assignment actions

`ajax/action.php` takes a JSON body `{"action": "...", "id": <ticket>, "value": ...}`. It is
a state-changing POST, so GLPI refuses it unless it passes its own CSRF check (the session
token header in GLPI 11, `Sec-Fetch-Site` and `Origin` in GLPI 12). Unknown actions and
unknown keys are answered with 400.

| `action`          | `value`                                   | Native right checked on the ticket                  |
| ----------------- | ----------------------------------------- | --------------------------------------------------- |
| `assign_me`       | none                                      | update, and assign or own/steal                     |
| `assign_other`    | `"user:<id>"` or `"group:<id>"`           | assign; the user must be able to own tickets in the entity of the ticket and active, the group assignable and visible there |
| `unassign_me`     | none                                      | update                                              |
| `unassign_others` | list of up to 50 `"user:<id>"`/`"group:<id>"` | assign; every entry must be assigned now, none may be the current user |

The change goes through the native `Ticket_User` and `Group_Ticket` classes. On success the
answer carries the ticket address in `redirect` and GLPI shows the message on the reloaded
page. Statuses: 404 when the ticket does not exist or cannot be seen (the same answer for
both), 403 without the right, 409 when the ticket is closed or deleted or the change is a
no-op (already assigned, not assigned), 400 for an assignee that cannot be assigned.
`unassign_others` is all or nothing: if one entry is not assigned, nothing is removed.

`ajax/search.php?action=<assign_other|unassign_others>&id=<ticket>[&q=<text>]` returns
`data` as a list of `{"id": "user:3", "label": "...", "detail": "..."}`: at most 10 users
and 10 groups for `assign_other` (already assigned ones left out), every assignee but the
current user for `unassign_others`. It needs the right to assign on the ticket.

## Ticket actions

The same endpoint also runs these actions. They need the central interface; self-service
users get 403.

| `action`          | `value`                                         | Native right checked on the ticket                           |
| ----------------- | ----------------------------------------------- | ------------------------------------------------------------ |
| `change_status`   | `"1"` New, `"2"` assigned, `"3"` planned, `"4"` pending | update; the status must be allowed by the profile matrix for the current one |
| `change_priority` | `"1"` to `"6"`                                  | update and the right to change the priority                  |
| `close`           | the solution text; none in direct mode          | quick solution: right to solve; direct close: update and a closed status allowed for the profile |

`close` follows the administrator setting `close_mode`. With `quick_solution` (default) the
text is required (up to 10000 characters) and becomes an `ITILSolution`, so GLPI applies
its own flow (solved, then approval and closing). With `direct_close` the ticket goes to
Closed and sending text is refused with 400. The bootstrap payload carries the mode as
`closeMode` and the choices of `change_status` and `change_priority` in `options`.

GLPI may apply business rules to a change; if it ends up with a different value the answer
is 422 and nothing is reported as done. Statuses: 409 when the value is already set or the
ticket is closed or deleted, 400 for a status the profile may not use.
