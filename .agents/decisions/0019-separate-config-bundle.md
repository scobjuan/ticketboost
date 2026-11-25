---
id: 0019
title: Configuration pages load a separate bundle
status: accepted
date: 2026-11-19
---

# 0019. Configuration pages load a separate bundle

## Context

The main bundle (`public/js/ticketboost.js`) loads on every GLPI page and has a budget of
15 KB gzip (RULES.md section 3); Phase 5 left it at 9.0 KB. Phase 6 adds a key recorder and
live conflict detection that only the admin page and the user preference tab need.

## Decision

The configuration UI is a second bundle, `public/js/ticketboost-config.js`, built from
`frontend/src/config/main.ts` and loaded only by the plugin configuration pages through a
`<script type="module">` in their own templates, never through `add_javascript_module`. It
has its own budget of 10 KB gzip, checked by `scripts/size-check.mjs`. The main bundle budget
and its rule of zero runtime dependencies are unchanged and apply to both bundles. The
config bundle may import the keymap modules (`binding.ts`, `validator.ts`, `catalog.ts`) that
the main bundle already ships; it must not import the panel, the leader or the actions.

## Alternatives considered

- Everything in the main bundle: every GLPI page would pay for code used on two pages.
- Raising the 15 KB budget: weakens a rule that exists to protect GLPI page weight.

## Consequences

The build runs Vite twice. The size check reports both bundles. Text for the config UI
still comes from PHP (`__()` in the Twig template), never from TypeScript.
