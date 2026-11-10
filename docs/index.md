# TicketBoost

Keyboard shortcuts for faster ticket handling in GLPI.

Handling a ticket in GLPI usually means many clicks: leave the ticket, assign someone,
change the status, close it, open the next one. TicketBoost puts those actions behind
the keyboard. Tap ++ctrl++, a compact panel shows the available shortcuts, and a single
key does the job.

!!! info "Project status"
    TicketBoost is under active development towards its first release (0.1.0). Pages
    marked **Planned** describe behavior that is specified but not released yet.

## Highlights

- **One leader key.** Tap and release ++ctrl++ (configurable) to open the panel;
  ++ctrl+c++ and ++ctrl+v++ keep working as usual.
- **Ticket actions.** Next and previous ticket (circular), back to the list, my tickets,
  assign to me or to someone else, unassign, change status or priority, close, and open
  the followup or task editor with ++ctrl+enter++ to send.
- **Safe by design.** Every action runs on the server through native GLPI classes, so
  rights, business rules and notifications are always respected.
- **For everyone.** Technicians and end users (self-service) each see only what their
  profile allows.
- **English and Spanish**, following the GLPI language of each user.
- **GLPI 11 and 12.**

## Where to go next

- [Install the plugin](getting-started/installation.md)
- [Learn the shortcuts](user-guide/shortcuts.md)
- [Configure it for your organization](admin-guide/configuration.md)
- [Contribute](development/contributing.md)
