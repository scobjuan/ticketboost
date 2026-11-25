---
id: 0020
title: Dependabot pull requests are exempt from the authorship rules
status: accepted
date: 2026-11-25
---

# 0020. Dependabot pull requests are exempt from the authorship rules

## Context

Dependabot reports new dependency versions by opening pull requests under its own account. Their
bodies quote upstream release notes, which contain emojis and may contain names that the guards
reject, so the "Conventions and project rules" job failed (pull request 10). The owner wants to
keep the automatic updates.

## Decision

Dependabot pull requests are the single exception to owner rules 1 and 2:

- CI checks only the title of a pull request whose author is `dependabot[bot]`; the body is not
  checked. The condition uses the pull request author, not the actor that triggered the run.
- Its branches and commits keep the bot as author.
- Every other check still applies: tree guards, commitlint on commits and title, tests and e2e.
- They are merged by squash with the repository's blank squash message, so only the title reaches
  `main` and the quoted release notes do not.

## Alternatives considered

- Remove Dependabot and update by hand: no automatic notice of new versions.
- Reapply each update on an owner branch: repeated manual work every week.
- Disable the pull request text check for everyone: weakens the rules for all contributors.

## Consequences

Dependency updates can be merged directly once CI passes. The history of `main` and the pull
request list show the bot for those changes. An update that needs extra work (for example a
Playwright image or a peer dependency limit) is still handled in the same pull request.
