---
title: Add a keyboard action
when: Adding a new action to the shortcut panel or changing what an existing action does.
---

# Add a keyboard action

The MVP action list is fixed in PLAN.md section 3.3. New actions need owner approval
and an update of that section.

1. Specify: id (snake_case), default key, context (ticket form or anywhere), interfaces
   (central, self-service), GLPI right required, server effect, success and error messages.
2. Translations first: add every label and message to `locales/ticketboost.pot`,
   `en_GB.po`, `es_ES.po` (see add-translation playbook).
3. PHP (from Phase 2 structure):
   - class in `src/Action/` implementing `ActionInterface`; register it in `ActionRegistry`
   - authorization: plugin right + native right on the item, checked server-side
   - only native GLPI classes; no raw SQL
   - PHPUnit: success, missing right, wrong entity, invalid input
4. TypeScript: action entry in `frontend/src/actions/`, default binding in the keymap,
   validator rejects conflicts; Vitest for the client logic.
5. Playwright spec in `e2e/specs/` covering the happy path and the rights filter.
6. Docs: keymap table in `docs/user-guide/shortcuts.md` and any admin setting.
7. Commit as `feat(actions): ...`. Changing a default binding is a breaking change.
