---
id: 0012
title: Agent-agnostic AI workflow
status: accepted
date: 2026-11-09
---

# 0012. Agent-agnostic AI workflow

## Context

Several coding agents may work on the project across sessions; each must know the rules, the plan and where the previous session stopped, without tool names in the repository.

## Decision

`AGENTS.md` is the only versioned entry point. `.agents/` holds RULES, PLAN, MEMORY (capped live state and session log), KNOWLEDGE (utilities registry and gotchas), ADRs, playbooks and templates. `scripts/agents-check.mjs` enforces caps, frontmatter, translations, no emojis and no attribution (tool names matched by SHA-256). Tool-specific filenames are local symlinks created by `pnpm agents:link` and excluded via `.git/info/exclude`.

## Alternatives considered

Commit tool-specific pointer files (leaves tool names in the repository).

## Consequences

Every session follows the protocol in AGENTS.md. The guard runs in pre-commit, commit-msg and CI.
