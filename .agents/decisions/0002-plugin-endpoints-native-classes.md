---
id: 0002
title: Run actions through plugin endpoints that use native GLPI classes
status: accepted
date: 2026-11-09
---

# 0002. Run actions through plugin endpoints that use native GLPI classes

## Context

Actions must work on any GLPI install, for admins and non-admins, and respect rights, business rules and notifications.

## Decision

The browser calls plugin AJAX endpoints (CSRF protected, session based). Endpoints re-check rights and call `Ticket`, `Ticket_User`, `Group_Ticket`, `ITILSolution`, `ITILFollowup` through `CommonDBTM` methods.

## Alternatives considered

GLPI REST API (requires per-install API enablement and tokens); clicking GLPI buttons from the DOM (breaks on every UI change).

## Consequences

Server code owns authorization. Behavior matches the GLPI UI because the same classes run.
