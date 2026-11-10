---
title: Start a session
when: At the beginning of every working session, before touching any file.
---

# Start a session

1. Read, in order: `.agents/RULES.md`, `.agents/MEMORY.md`, the current phase in
   `.agents/PLAN.md` (section 8 and any phase detail), `.agents/KNOWLEDGE.md`.
2. Run `git status` and `git log --oneline -10`. If the working tree is dirty and MEMORY
   "In progress" does not explain it, ask the owner before changing anything.
3. Run `make install` if `node_modules` is missing, then `pnpm agents:check`.
4. If the task touches the running plugin: `make up` and `make install-plugin`.
5. Confirm the task against MEMORY "Next steps". If the owner asks for something else,
   do it, and record the change of direction at the end of the session.
6. Pick the playbook that matches the task, if any.
