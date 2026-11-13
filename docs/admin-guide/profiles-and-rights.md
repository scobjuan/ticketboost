# Profiles and rights

TicketBoost adds a **TicketBoost** tab to the profile form (**Administration > Profiles**)
with two options:

| Option                      | Effect                                        |
| --------------------------- | --------------------------------------------- |
| Use the keyboard shortcuts  | The user can use the shortcut panel           |
| Configure TicketBoost       | The user can change the installation defaults |

Choosing _Configure_ also enables _Use_. The tab is read-only for administrators who
cannot update profiles.

On installation every profile, self-service ones included, can use the shortcuts, and
profiles that can update the GLPI configuration can also configure the plugin. Clear _Use
the keyboard shortcuts_ on a profile to disable the plugin for it. Reinstalling the
plugin never changes the choices already made.

The plugin right only controls access to TicketBoost. Every action is also checked
against the native GLPI rights of the user on the specific ticket (for example _assign_,
_change priority_, _update_), in the same way as the GLPI interface does.
