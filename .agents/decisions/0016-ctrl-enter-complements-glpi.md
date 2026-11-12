---
id: 0016
title: Ctrl+Enter complements GLPI's native submit shortcut
status: accepted
date: 2026-11-12
---

# 0016. Ctrl+Enter complements GLPI's native submit shortcut

## Context

PLAN.md section 3.3 says Ctrl+Enter sends the focused followup or task form. Reading the
GLPI 11.0.12 and 12.0.1 sources showed that GLPI already does this in two places:
`src/Html.php` registers a TinyMCE shortcut (`editor.addShortcut('ctrl+13', ...)`) that
saves the editor and calls `submitparentForm()`, and `public/js/common.js` binds Ctrl+Enter
on every `#page form textarea`. `submitparentForm()` clicks the single `add`/`update`
submit button of the form. A second handler in the same places would send the form twice
(only partly guarded by `data-submit-once`).

## Decision

The plugin handles Ctrl+Enter (and Cmd+Enter) only where GLPI does not: focus on any other
field of a timeline followup or task form (selects, duration, the on-demand editor
placeholder), detected by a form action ending in `itilfollowup.form.php` or
`tickettask.form.php`. Textareas and TinyMCE iframes are left to GLPI. The form is sent
with `form.requestSubmit(button)` on its single `add`/`update` button, after
`tinymce.triggerSave()`, so GLPI's submit handlers (validation, submit once) run as for a
click. With zero or several such buttons nothing happens, as in GLPI.

## Alternatives considered

- Handle Ctrl+Enter everywhere and stop GLPI's handlers: duplicates GLPI behavior and
  depends on listener order inside TinyMCE.
- Do nothing (GLPI covers it): focus outside the textarea, common in task forms, would
  not send the form.

## Consequences

The shortcut works without the leader and without the bootstrap payload, so it is active
from Phase 1 on. If GLPI changes its native shortcut, revisit this ADR.
