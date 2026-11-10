---
id: 0003
title: Leader key: tap-and-release Ctrl opening a which-key panel
status: accepted
date: 2026-11-09
---

# 0003. Leader key: tap-and-release Ctrl opening a which-key panel

## Context

The owner wants combinations with preventDefault and a small panel listing next keys after a leader. Holding Ctrl and pressing letters collides with copy, paste, find, save and browser-reserved chords.

## Decision

Pressing and releasing the leader key (Ctrl by default) with no other key in between opens the panel; it stays open, plain single keys run actions, Esc closes. Ctrl+C/V never trigger it. Leader key, tap count (1-3), tap window and an alternative hold mode are configurable by the admin and per user.

## Alternatives considered

Hold Ctrl + letter (few free letters, browser conflicts); Alt+K or Ctrl+K chords (owner preferred Ctrl); command palette only.

## Consequences

Key listeners run in capture phase on the document and inside TinyMCE iframes. Browser-reserved chords are rejected by the keymap validator.
