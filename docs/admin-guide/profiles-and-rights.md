# Profiles and rights

**Planned for 0.1.0.**

TicketBoost adds one right, **TicketBoost**, on the profile form:

| Right  | Effect                                        |
| ------ | --------------------------------------------- |
| Read   | The user can use the shortcut panel           |
| Update | The user can change the installation defaults |

On installation every profile receives _Read_, and profiles that can update the GLPI
configuration also receive _Update_. Remove _Read_ from a profile to disable the plugin
for it.

The plugin right only controls access to TicketBoost. Every action is also checked
against the native GLPI rights of the user on the specific ticket (for example _assign_,
_change priority_, _update_), in the same way as the GLPI interface does.
