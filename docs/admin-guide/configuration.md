# Configuration

**Planned for 0.1.0.** In **Setup > Plugins > TicketBoost** an administrator with the
_configure_ right can set the defaults for the whole installation:

| Setting      | Default                                     | Description                                                                                                                                                                      |
| ------------ | ------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Leader key   | Ctrl                                        | Key that opens the panel                                                                                                                                                         |
| Taps         | 1                                           | Taps needed to open the panel (1 to 3)                                                                                                                                           |
| Tap length   | 300 ms                                      | Longest press that still counts as a tap                                                                                                                                         |
| Tap window   | 400 ms                                      | Maximum time between taps                                                                                                                                                        |
| Mode         | Tap                                         | Tap and release, or hold for a given time                                                                                                                                        |
| Hold time    | 500 ms                                      | How long the key must be held in hold mode                                                                                                                                       |
| Key bindings | See [Shortcuts](../user-guide/shortcuts.md) | Default key of every action                                                                                                                                                      |
| Close mode   | Quick solution                              | `Quick solution`: asks for a solution text or template and follows the normal GLPI solve and approval flow. `Direct close`: sets the status to Closed when the profile allows it |

Users can override the keyboard settings for themselves unless the plugin is disabled
for their profile.
