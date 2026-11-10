# TicketBoost

Keyboard shortcuts for faster ticket handling in GLPI.

[![CI](https://github.com/scobjuan/ticketboost/actions/workflows/ci.yml/badge.svg)](https://github.com/scobjuan/ticketboost/actions/workflows/ci.yml)
[![License: GPL v3+](https://img.shields.io/badge/license-GPLv3%2B-blue.svg)](LICENSE)
![GLPI 11 | 12](https://img.shields.io/badge/GLPI-11%20%7C%2012-green.svg)

Handling a ticket in GLPI takes many clicks: leave the ticket, assign someone, change the
status, close it, open the next one. With TicketBoost you tap **Ctrl**, a compact panel
shows the available shortcuts, and a single key does the job.

> Status: under development towards 0.1.0. The plan and progress are public in
> [.agents/PLAN.md](.agents/PLAN.md) and [.agents/MEMORY.md](.agents/MEMORY.md).

## Features (0.1.0)

| Key (after tapping Ctrl) | Action                                                      |
| ------------------------ | ----------------------------------------------------------- |
| `N` / `P`                | Next / previous ticket of the current list, circular        |
| `L`                      | Back to the list                                            |
| `M`                      | My tickets                                                  |
| `A` / `O`                | Assign to me / to another technician or group               |
| `U` / `X`                | Unassign me / unassign others                               |
| `S` / `R`                | Change status / priority                                    |
| `C`                      | Close (quick solution or direct close, chosen by the admin) |
| `F` / `T`                | Open the followup / task editor (`Ctrl+Enter` sends)        |
| `?`                      | Show all shortcuts                                          |

- Ctrl+C, Ctrl+V and other combinations keep working: only a tap of Ctrl alone opens the panel.
- Leader key, number of taps, timing and every binding are configurable by the admin and by each user.
- Every action runs through native GLPI classes: rights, rules and notifications are respected.
- Works in the central and the self-service interfaces, filtered by each profile's rights.
- English and Spanish.

## Requirements

GLPI 11.0.x or 12.0.x, PHP 8.2 or later (8.3 or later for GLPI 12).

## Installation

Download `ticketboost-X.Y.Z.tar.bz2` from the
[releases](https://github.com/scobjuan/ticketboost/releases), extract it into GLPI's
`plugins/` directory and enable it in **Setup > Plugins**. Details in the
[documentation](https://scobjuan.github.io/ticketboost/getting-started/installation/).

## Documentation

<https://scobjuan.github.io/ticketboost/> (sources in [docs/](docs/)).

## Development

```bash
make install         # Node dependencies and git hooks
make up              # GLPI 11 on :8011 and GLPI 12 on :8012 (podman-compose or docker compose)
make install-plugin  # build and enable the plugin in both
make check           # lint, unit tests with coverage gates, guards, docs
make e2e             # Playwright end-to-end tests
```

See [CONTRIBUTING.md](CONTRIBUTING.md). Contributors using coding agents start at
[AGENTS.md](AGENTS.md).

## License

[GPLv3 or later](LICENSE). Copyright 2026 Juan A. Escobar.
