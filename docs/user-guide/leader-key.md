# Leader key and panel

**Planned for 0.1.0.**

## Opening the panel

The panel opens when you **tap and release** the leader key (++ctrl++ by default)
without pressing any other key in between. Combinations such as ++ctrl+c++ or
++ctrl+v++ never open it, so copy and paste keep working. The panel also works while
you are typing in the followup or task editor.

Your administrator, and you in your [personal settings](personal-settings.md), can change:

- the leader key (++ctrl++, ++alt++, ++shift++ or ++meta++)
- how many taps are needed (1 to 3) and the time allowed between taps
- an alternative mode: open the panel after holding the key for a given time

## Using the panel

- It lists only the actions available on the current page and allowed by your profile.
- Press the key shown next to an action to run it. Some actions open a sub-menu
  (status, priority) or a search field (assign to another person).
- In a sub-menu, press the number shown next to an option.
- In a search field, type to filter, move with ++arrow-up++ and ++arrow-down++ and press
  ++enter++ to choose. When several people can be chosen (unassign others), ++enter++
  marks or unmarks a person and ++ctrl+enter++ confirms.
- ++esc++ goes back one level and closes the panel from the first level; ++backspace++
  also goes back (in a search field, only when it is empty). A click outside closes it.
- ++question++ shows every shortcut.

## Keys the browser keeps

Browsers reserve some combinations (for example ++ctrl+w++, ++ctrl+t++, ++ctrl+n++,
++ctrl+tab++) and never send them to web pages. TicketBoost does not use them and
rejects them as custom bindings. The same applies to the GLPI shortcuts
++ctrl+alt+g++ (search), ++ctrl+alt+e++ (entity selector) and ++ctrl+arrow-left++ /
++ctrl+arrow-right++ (previous and next item), and to ++esc++, ++tab++ and
++backspace++, which the panel needs.

## Keys that depend on the keyboard layout

Letters and digits are bound to the physical key, so they stay in place on any layout.
Symbols such as ++question++ are bound to the character itself, so they work on every
layout, wherever the symbol sits.
