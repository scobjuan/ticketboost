---
id: 0017
title: Binding notation with physical keys and typed characters
status: accepted
date: 2026-11-12
---

# 0017. Binding notation with physical keys and typed characters

## Context

PLAN.md section 3.1 says action keys match `KeyboardEvent.code`, so letters work on any
layout and with macOS Option. That fails for symbols: the default help key `?` is
Shift+Slash on a US layout and Shift+Minus on a Spanish one, so no single `code` works.
Keymaps are stored by the server and edited by users (Phase 6), so the notation must be
text that both TypeScript and PHP can parse and compare.

## Decision

A binding is text `[Ctrl+][Alt+][Shift+][Meta+]Key`, modifiers in any order, written back
in that canonical order for comparisons and storage. `Key` is either a
`KeyboardEvent.code` value from a fixed list (`KeyA`-`KeyZ`, `Digit0`-`Digit9`,
`Numpad0`-`Numpad9`, `F1`-`F12`, arrows, named keys, punctuation codes), matched against
`event.code` with exact modifiers, or a single printable character (`?`), matched against
`event.key` while ignoring Shift. Reserved bindings (browser-reserved chords, GLPI native
chords, Escape, Tab, Shift+Tab and Backspace used by the panel) are rejected by the
validator and skipped at runtime. Submenu options use Digit1-9 and Numpad1-9.

## Alternatives considered

- `code` only: no layout-independent way to bind `?`.
- `key` only: letters change with layouts and with macOS Option.

## Consequences

The PHP validator in Phase 2 must implement the same grammar and the same
`RESERVED_BINDINGS` list as `frontend/src/keymap/` (shared test cases advised). A
character binding and a code binding that produce the same key on one layout (`?` and
`Shift+Slash`) are not reported as a conflict.
