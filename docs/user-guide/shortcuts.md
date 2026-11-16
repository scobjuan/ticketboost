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
search. If the ticket is not in that list, or you opened it from a link or a notification
and there is no list yet, TicketBoost walks your unresolved tickets (assigned to you; in
self-service, requested by you), most urgent first, and a notice says so on the page that
opens. Tickets you cannot open are skipped. When there is no other ticket to go to, a
message says so.

## Assigning

These actions work on the ticket you have open and reload it when they finish, so the
timeline and the assignees are up to date. GLPI does the work (status, history and
notifications behave as in the ticket form) and a notice says what happened.

| Action | What it does                                                                                              |
| ------ | --------------------------------------------------------------------------------------------------------- |
| ++a++  | Assigns the ticket to you. A new ticket becomes _Processing (assigned)_                                   |
| ++o++  | Opens a search box with the users who can own tickets in the entity of the ticket, and the assignable groups. ++enter++ assigns the selected one |
| ++u++  | Removes you from the ticket                                                                               |
| ++x++  | Lists the current assignees except you. ++enter++ marks or unmarks one, ++ctrl+enter++ removes the marked ones |

Typing in a search box waits for the new results: ++enter++ does nothing until the list
matches what you typed, so you never pick someone from an outdated list. People and groups
already assigned are not offered again. Closed and deleted tickets cannot be changed.

Which of these you see depends on your profile: assigning to others needs the GLPI right
to assign tickets; assigning yourself needs the right to own or steal tickets.

## Changing the ticket

Like the assignment actions, these work on the open ticket, reload it when they finish and
let GLPI show a notice. They are only offered in the central interface and only to profiles
with the matching GLPI right.

| Action | What it does                                                                                                         |
| ------ | -------------------------------------------------------------------------------------------------------------------- |
| ++s++  | Opens the statuses: New, Processing (assigned), Processing (planned) and Pending. Press a digit to choose one. The statuses the profile may not move to are refused |
| ++r++  | Opens the priorities, from Very low to Major. Press a digit to choose one                                            |
| ++c++  | Closes the ticket. By default it asks for the solution text (++ctrl+enter++ sends it) and creates the solution, so GLPI follows its normal solve and approval flow. If the administrator chose _Direct close_, it sets the status to Closed at once |
| ++f++  | Opens the followup form under the timeline with the cursor in the editor. ++ctrl+enter++ sends it                   |
| ++t++  | The same for a task                                                                                                  |

The solution is stored as plain text. Solution templates are not offered yet.
