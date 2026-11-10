---
id: 0004
title: Circular next/previous over the GLPI session list with a fallback
status: accepted
date: 2026-11-09
---

# 0004. Circular next/previous over the GLPI session list with a fallback

## Context

GLPI already exposes previous/next navigation built from `$_SESSION['glpilistitems']`, filled by the last search.

## Decision

Next/previous reuse that list and wrap around (last -> first, first -> last). When no list exists (ticket opened from a link), fall back to the user's unresolved tickets ordered by priority desc then date asc, with a discreet notice.

## Alternatives considered

Always the user's own queue; per-user configurable source.

## Consequences

Behavior matches what users see in GLPI lists. The fallback query must respect entity and rights restrictions.
