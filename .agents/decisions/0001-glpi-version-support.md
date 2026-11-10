---
id: 0001
title: Support GLPI 11.x and 12.x only
status: accepted
date: 2026-11-09
---

# 0001. Support GLPI 11.x and 12.x only

## Context

GLPI 12.0.1 was released on 2026-10-28, 11.0.x is still maintained, and 10.0.28 was the last 10.x release (end of life). GLPI 10 uses a different architecture (no Symfony kernel, different asset serving).

## Decision

Support GLPI 11.0.0 up to 12.0.99 from a single codebase and release line. PHP >= 8.2 (GLPI 12 requires PHP >= 8.3).

## Alternatives considered

Also support 10.x (duplicated code paths, EOL platform); only 12.x (excludes most current installs).

## Consequences

CI matrix covers both majors. A new GLPI major is added through `.agents/playbooks/add-glpi-version.md`.
