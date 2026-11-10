---
id: 0005
title: Configuration: admin defaults, profile right, user overrides
status: accepted
date: 2026-11-09
---

# 0005. Configuration: admin defaults, profile right, user overrides

## Context

Both admins and regular users must be able to use the plugin; companies want standard defaults, users want personal layouts.

## Decision

Admin defaults stored with `Config::setConfigurationValues('plugin:ticketboost')`. Plugin right `plugin_ticketboost` (READ use, UPDATE configure), granted READ to all profiles on install. Per-user preferences table with remapping, disabling and conflict detection.

## Alternatives considered

Admin only; fixed bindings.

## Consequences

Conflict detection is implemented in TypeScript (live) and PHP (authoritative), both tested.
