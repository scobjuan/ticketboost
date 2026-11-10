# Installation

## Requirements

| Component | Supported                                    |
| --------- | -------------------------------------------- |
| GLPI      | 11.0.x and 12.0.x                            |
| PHP       | 8.2 or later (GLPI 12 requires 8.3 or later) |
| Browser   | Current Chrome, Edge, Firefox and Safari     |

## From a release archive (recommended)

1. Download `ticketboost-X.Y.Z.tar.bz2` from the
   [releases page](https://github.com/scobjuan/ticketboost/releases).
2. Extract it into the `plugins/` directory of GLPI. The result must be
   `plugins/ticketboost/setup.php`.
3. In GLPI go to **Setup > Plugins**, then **Install** and **Enable** TicketBoost.
   With the console: `php bin/console plugin:install --username=<admin> ticketboost`
   and `php bin/console plugin:activate ticketboost`.

## From the GLPI marketplace

**Planned.** Once published, TicketBoost can be installed from **Setup > Plugins >
Marketplace**.

## From source

Source checkouts do not contain the built JavaScript and compiled translations. Build
them first (Node 24 and pnpm are required):

```bash
git clone https://github.com/scobjuan/ticketboost.git plugins/ticketboost
cd plugins/ticketboost
pnpm install && make build
```

## Upgrade and uninstall

- Upgrade: replace the `plugins/ticketboost` directory with the new release and click
  **Upgrade** in **Setup > Plugins** if GLPI asks for it.
- Uninstall: **Setup > Plugins > Uninstall** removes the plugin tables and settings.
