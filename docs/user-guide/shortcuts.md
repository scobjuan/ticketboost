# Shortcuts

**Planned for 0.1.0.** Default bindings, pressed after opening the panel with the
leader key. Every binding can be changed.

| Key                      | Action                                                                  | Where    | Self-service |
| ------------------------ | ----------------------------------------------------------------------- | -------- | ------------ |
| ++n++ or ++arrow-right++ | Next ticket of the current list (after the last one, back to the first) | Ticket   | Yes          |
| ++p++ or ++arrow-left++  | Previous ticket (before the first one, to the last)                     | Ticket   | Yes          |
| ++l++                    | Back to the list                                                        | Ticket   | Yes          |
| ++m++                    | My tickets (assigned to me; in self-service, requested by me)           | Anywhere | Yes          |
| ++a++                    | Assign to me                                                            | Ticket   | If allowed   |
| ++o++                    | Assign to another technician or group                                   | Ticket   | If allowed   |
| ++u++                    | Unassign me                                                             | Ticket   | If allowed   |
| ++x++                    | Unassign other users or groups                                          | Ticket   | If allowed   |
| ++s++                    | Change status                                                           | Ticket   | If allowed   |
| ++c++                    | Close (with a quick solution or directly, as configured)                | Ticket   | If allowed   |
| ++f++                    | Open the followup editor                                                | Ticket   | Yes          |
| ++t++                    | Open the task editor                                                    | Ticket   | If allowed   |
| ++r++                    | Change priority                                                         | Ticket   | If allowed   |
| ++question++             | Show all shortcuts                                                      | Anywhere | Yes          |

Without the leader key: ++ctrl+enter++ (++cmd+enter++ on macOS) sends the followup or
task you are writing, also when the focus is on another field of the form, such as the
duration of a task.

## Which list do next and previous use?

The same list GLPI uses for its own previous/next arrows: the result of your last
search or ticket view. If you opened the ticket from a link or a notification and there
is no list yet, TicketBoost uses your unresolved tickets, ordered by priority and then
by date, and tells you so.
