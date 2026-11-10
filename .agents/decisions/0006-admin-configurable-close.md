---
id: 0006
title: Close action behavior is chosen by the admin
status: accepted
date: 2026-11-09
---

# 0006. Close action behavior is chosen by the admin

## Context

In GLPI a ticket is normally solved with a solution, then closed after approval; some companies close directly.

## Decision

Setting `close_mode`: `quick_solution` (inline solution text or template, creates an ITILSolution and lets GLPI apply its flow) or `direct_close` (sets status Closed when rights allow).

## Alternatives considered

Always quick solution; always direct close.

## Consequences

Both modes need PHPUnit and e2e coverage.
