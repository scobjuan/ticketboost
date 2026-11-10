---
id: 0013
title: Documentation with MkDocs Material on GitHub Pages
status: accepted
date: 2026-11-09
---

# 0013. Documentation with MkDocs Material on GitHub Pages

## Context

The project is open source and needs user, admin, integration and contributor documentation.

## Decision

Markdown sources in `docs/` (readable on GitHub), built with MkDocs Material in strict mode on every PR and deployed to GitHub Pages on release.

## Alternatives considered

Plain Markdown only.

## Consequences

Behavior changes update docs in the same PR.
