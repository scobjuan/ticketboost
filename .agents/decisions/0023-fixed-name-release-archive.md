---
id: 0023
title: The release also publishes the archive under a fixed name
status: accepted
date: 2026-11-25
---

# 0023. The release also publishes the archive under a fixed name

## Context

The marketplace reads a `download_url` per version in `plugin.xml`. The release tooling rewrites one
version number per line, and the address of `ticketboost-X.Y.Z.tar.bz2` holds it twice.

## Decision

`scripts/build-release.sh` also writes `ticketboost.tar.bz2`, the release uploads both files, and the
`download_url` is `.../releases/download/vX.Y.Z/ticketboost.tar.bz2`. `PluginManifestTest` checks it.

## Alternatives considered

Editing the address by hand on every release; dropping `download_url`, which marketplace installs need.

## Consequences

Each release carries two identical files. Extends ADR 0011, which stays valid.
