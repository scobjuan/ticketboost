---
title: Add or change user-facing text
when: Any new or changed string shown to users (UI, messages, plugin.xml).
---

# Add or change user-facing text

1. In PHP use `__('Text', 'ticketboost')` (or `_n()` for plurals); English is the msgid.
   Never put user-facing text in TypeScript; pass it through the bootstrap payload.
2. Add the msgid to `locales/ticketboost.pot`, `locales/en_GB.po` (msgstr = English) and
   `locales/es_ES.po` (msgstr = Spanish, neutral Spanish understood in Spain and Latin America).
3. `make locales` compiles `.mo` files and regional copies. `.mo` files are not committed.
4. `pnpm agents:check` fails if any es_ES entry is untranslated.
5. For `plugin.xml`, update both the `<en>` and `<es>` elements.
6. Commit as `feat(i18n): ...` or `fix(i18n): ...`.
