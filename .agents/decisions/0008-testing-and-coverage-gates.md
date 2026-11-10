---
id: 0008
title: Testing stack and blocking coverage gates
status: accepted
date: 2026-11-09
---

# 0008. Testing stack and blocking coverage gates

## Context

Tests must gate every merge.

## Decision

PHPUnit inside the official GLPI CI image (Unit and Integration with DbTestCase), Vitest with happy-dom, Playwright against real GLPI 11 and 12 containers (chromium on both, firefox and webkit on 12). Coverage: TypeScript >= 90%, PHP >= 85% lines.

## Alternatives considered

Cypress (weaker keyboard fidelity and WebKit support); lower thresholds.

## Consequences

Bug fixes start with a failing test. E2E uses default GLPI accounts and seeded data.
