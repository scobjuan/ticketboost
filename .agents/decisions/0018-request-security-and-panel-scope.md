---
id: 0018
title: Request security, self-service rights and panel scope in the backend core
status: proposed
date: 2026-11-12
---

# 0018. Request security, self-service rights and panel scope in the backend core

## Context

Building the backend core against the real GLPI 11 and 12 sources and containers showed
four things that PLAN.md did not foresee:

- CSRF works differently per version. A kernel listener (`CheckCsrfListener`) runs before
  any legacy endpoint on every non-GET request. In GLPI 11 it requires the session token
  (header `X-Glpi-Csrf-Token` for XMLHttpRequest, otherwise the `_glpi_csrf_token` field).
  In GLPI 12 `Session::checkCSRF()` is deprecated and the listener validates
  `Sec-Fetch-Site` and `Origin` instead.
- For profiles of the `helpdesk` interface GLPI drops, in `Profile::cleanProfile()`, every
  right that is not in `Profile::$helpdesk_rights`. The plugin right never reached a
  self-service session, so the panel stayed off for them.
- `Session::haveRight()` returns the masked right (an int) in GLPI 11 and a bool in
  GLPI 12.
- A context endpoint (`ajax/context.php`) would only repeat what the page URL already
  says: both versions serve an existing ticket at `/front/ticket.form.php?id=N`.

## Decision

1. CSRF stays with the GLPI kernel in both versions; the plugin does not implement a
   second token. `RequestGuard` checks, in this order: session (401), HTTP method (405),
   the `X-Requested-With: XMLHttpRequest` header (400) and the plugin right (403).
   Requiring the AJAX header makes GLPI 11 read the token from the header and keeps the
   endpoints out of reach of plain form posts and links. The client sends the header and,
   when the page has the GLPI 11 meta tag, the token. This satisfies RULES.md section 3
   (CSRF on every request) because the kernel enforces it before plugin code runs.
2. The plugin adds `plugin_ticketboost` to `Profile::$helpdesk_rights` in
   `plugin_init_ticketboost()` (`Rights::exposeToSelfService()`), so self-service
   profiles can be enabled.
3. Rights are read through values that are only truthy: `ActionAvailability` types its
   capabilities as `bool|int` and converts where they are consumed. Never compare them
   with `=== true`.
4. There is no `ajax/context.php`. The frontend detects the ticket page from the URL
   (`context/detect.ts`); the server sends the actions the profile may use, and the
   native right on the specific item is checked again by each action endpoint (Phase 3
   onwards). The bootstrap payload is the only read endpoint.
5. `ActionAvailability::IMPLEMENTED` lists the actions that have a working endpoint; the
   panel never offers anything else. Each phase adds its actions to it.
6. The preferences table is created with DDL through `DBmysql::doQuery()`, the documented
   way for plugins; all other database access uses the GLPI API (RULES.md section 3
   forbids raw queries, not DDL).

## Alternatives considered

- Own CSRF token for the plugin: duplicates a mechanism that differs per version and
  would still need the kernel's check.
- Keep `ajax/context.php` from the plan: an extra request on every page for no new data.
- Offer every action in the panel from the start: it would list keys that do nothing.

## Consequences

- PLAN.md section 8 (Phase 2: "bootstrap/context endpoints") and the endpoint table in
  `docs/reference/endpoints.md` drop `context.php`; owner approval of this ADR confirms it.
- The first POST endpoint (Phase 3 or 4) must add an e2e spec proving that a POST without
  the CSRF protection is refused on GLPI 11 and 12.
- A right that must work for self-service has to be exposed the same way.
