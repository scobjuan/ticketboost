---
id: 0014
title: Name TicketBoost and license GPLv3+
status: accepted
date: 2026-11-09
---

# 0014. Name TicketBoost and license GPLv3+

## Context

The plugin needs a unique marketplace key and an open-source license compatible with GLPI.

## Decision

Display name TicketBoost, key `ticketboost`, namespace `GlpiPlugin\Ticketboost`, repository scobjuan/ticketboost. License GPLv3+, the same family as GLPI. Discoverability through the tagline and marketplace tags (keyboard, shortcuts, hotkeys, productivity).

## Alternatives considered

Hotkeys, QuickKeys, Keyflow; MIT.

## Consequences

Every PHP file carries the header in `tools/HEADER`.
