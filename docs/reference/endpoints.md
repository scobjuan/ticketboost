# Endpoints

**Planned for 0.1.0.** TicketBoost does not use the GLPI REST API. The browser bundle
calls the plugin endpoints below with the user session and the GLPI CSRF token. Every
request is authorized again on the server.

| Endpoint             | Method | Purpose                                                                        |
| -------------------- | ------ | ------------------------------------------------------------------------------ |
| `ajax/bootstrap.php` | GET    | Effective keymap, available actions and translated labels for the current user |
| `ajax/context.php`   | GET    | Actions available on the current ticket                                        |
| `ajax/action.php`    | POST   | Runs an action on a ticket                                                     |
| `ajax/navigate.php`  | GET    | Next, previous or list URL                                                     |
| `ajax/search.php`    | GET    | Technicians and groups that can be assigned                                    |

Responses are JSON: `{"ok": true, "message": "...", "data": {}, "redirect": "..."}`.
