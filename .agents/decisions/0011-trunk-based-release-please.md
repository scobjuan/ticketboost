---
id: 0011
title: Trunk-based flow and release-please with the owner's token
status: accepted
date: 2026-11-09
---

# 0011. Trunk-based flow and release-please with the owner's token

## Context

All git and GitHub artifacts must be authored by the owner; versions follow SemVer from Conventional Commits.

## Decision

Protected `main`, short branches, squash merges with Conventional Commit titles. release-please runs with the owner's personal access token (`RELEASE_PLEASE_TOKEN`) so release PRs, tags and releases are authored by the owner. The release job attaches `ticketboost-X.Y.Z.tar.bz2`. Version markers live in `setup.php` and `plugin.xml`.

## Alternatives considered

GitFlow; semantic-release; bot-authored releases.

## Consequences

First release is forced to 0.1.0 with a `Release-As: 0.1.0` footer. Pre-1.0 breaking changes bump minor.
