---
id: 0007
title: Frontend in TypeScript without a framework, bundled with Vite
status: accepted
date: 2026-11-09
---

# 0007. Frontend in TypeScript without a framework, bundled with Vite

## Context

The bundle loads on every GLPI page next to jQuery, Bootstrap and GLPI's own Vue build.

## Decision

TypeScript strict, zero runtime dependencies, Vite library build into `public/js/ticketboost.js` and `public/css/ticketboost.css`, CSS classes prefixed `tb-`, budget 15 KB gzip.

## Alternatives considered

Vue 3 (size, possible clash with GLPI's Vue); plain JavaScript (no types).

## Consequences

UI is built with small DOM helpers. Everything runs through `safe()` so the plugin cannot break GLPI.
