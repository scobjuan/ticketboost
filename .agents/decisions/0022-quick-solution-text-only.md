---
id: 0022
title: The quick solution takes free text only
status: accepted
date: 2026-11-25
---

# 0022. The quick solution takes free text only

## Context

ADR 0006 and PLAN.md describe the quick solution as "inline solution text or template". Phase 5
shipped the text only. The owner decided on 2026-11-25 how 0.1.0 closes this gap.

## Decision

Setting `close_mode`, chosen by the administrator: `quick_solution` (the user types the
solution; it creates an `ITILSolution` and GLPI applies its flow) or `direct_close` (status
Closed when the rights allow). 0.1.0 does not offer `SolutionTemplate`; adding it is a later
feature outside the first release.

## Alternatives considered

Add a template picker before 0.1.0: more scope (picker, template rights per entity, rendering
of template variables) right before the first release.

## Consequences

Replaces ADR 0006. The user guide already says templates are not offered yet.
