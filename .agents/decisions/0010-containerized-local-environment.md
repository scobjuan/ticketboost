---
id: 0010
title: Local environment fully in containers
status: accepted
date: 2026-11-09
---

# 0010. Local environment fully in containers

## Context

The host has Podman and Node but no PHP or Docker; CI must match local runs.

## Decision

`compose.yaml` (MariaDB, glpi/glpi 11 on :8011 and 12 on :8012, tools11/tools12 with testdb under the `tools` profile) driven by the Makefile. Works with podman-compose and docker compose (`COMPOSE` variable).

## Alternatives considered

Install PHP and Composer on the host; CI only.

## Consequences

Developers need podman-compose or docker compose. Ports above 1024 for rootless Podman.
