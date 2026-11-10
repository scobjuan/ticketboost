# Working with coding agents

TicketBoost is designed so that any coding agent, in any session, can pick up the work
where the previous session stopped and follow the same rules. Nothing in the repository
is specific to one tool.

| File                   | Purpose                                                                            |
| ---------------------- | ---------------------------------------------------------------------------------- |
| `AGENTS.md`            | Entry point: project summary, layout, commands and the mandatory session protocol  |
| `.agents/RULES.md`     | Binding rules; they override tool defaults                                         |
| `.agents/PLAN.md`      | Approved scope, specification and roadmap                                          |
| `.agents/MEMORY.md`    | Live state: current state, in progress, next steps, blockers, session log (capped) |
| `.agents/KNOWLEDGE.md` | Registry of reusable utilities, patterns and gotchas (capped)                      |
| `.agents/decisions/`   | Architecture decision records                                                      |
| `.agents/playbooks/`   | Step-by-step procedures for repeatable tasks                                       |
| `.agents/templates/`   | Templates for ADRs, playbooks and session log entries                              |

## Session protocol

Every session starts by reading RULES, MEMORY, the current phase of PLAN and KNOWLEDGE,
and ends by updating MEMORY and KNOWLEDGE (see `.agents/playbooks/`).

## Guards

`pnpm agents:check` runs in the pre-commit hook and in CI. It rejects emojis, attribution
trailers and tool names, untranslated Spanish strings, missing ADR or playbook metadata,
and agent files over their size limits. The commit-msg hook applies the attribution
check to commit messages; CI applies it to pull request titles and bodies.

## Tool-specific filenames

If a tool reads a different filename than `AGENTS.md`, create a local symlink with
`pnpm agents:link <FILENAME>`. The link is added to `.git/info/exclude` and never
committed.
