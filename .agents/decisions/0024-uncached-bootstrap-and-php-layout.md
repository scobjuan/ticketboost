---
id: 0024
title: The bootstrap payload is not cached and the PHP code is grouped by feature
status: accepted
date: 2026-11-27
---

# 0024. The bootstrap payload is not cached and the PHP code is grouped by feature

## Context

PLAN.md section 3.5 asked for a bootstrap payload cached per session, and section 4 sketched
a `src/` tree with one class per action, an action registry and migration steps per version.
The implementation of 0.1.0 differs on both points.

## Decision

1. `ajax/bootstrap.php` builds the payload on every request and answers `Cache-Control:
no-store`. Changes to rights, administrator defaults and personal settings apply on the
   next page load.
2. `src/` is grouped by feature (`Assignment/`, `Ticketing/`, `Navigation/`, `Keymap/`,
   `Config/`, `Bootstrap/`, `Http/`, `Install/`, `Action/` for the router). Conflict
   detection and the default keymap live in `Keymap/`. `Install\Installer` is the whole
   installation; migration steps are added with the first schema change after 0.1.0.

## Alternatives considered

- Cache the payload in the session: it needs invalidation whenever rights, defaults or
  personal settings change, including changes made by another user.
- Move the classes into the planned tree: churn without a change in behaviour.

## Consequences

Every page load of an authenticated user costs one small request. If it ever matters,
caching needs a new ADR with its invalidation rules.
