---
title: End a session
when: Before finishing any working session, even a short or failed one.
---

# End a session

1. Run the checks relevant to what changed (`make check`; `make e2e` when behavior changed).
   Report failures honestly in MEMORY instead of hiding them.
2. Update `.agents/MEMORY.md`:
   - `Updated:` date, version and phase in the header
   - "Current state": what is true now (replace, do not append history)
   - "In progress": unfinished work with its branch name
   - "Next steps": ordered; the first item is the exact next action
   - "Blockers and waiting on owner", "Open questions", "Environment status"
   - "Session log": add one entry (date, done, stopped at, next); keep the last 5 entries
   - delete anything obsolete or resolved (git keeps history)
3. Update `.agents/KNOWLEDGE.md` with every new reusable utility (path, signature,
   purpose), pattern and gotcha discovered. Remove entries that became false.
4. New or changed decisions: ADR in `.agents/decisions/` and a row in its README.
5. Run `pnpm agents:check` and commit with a Conventional Commit authored by the owner.
   Do not push unless the owner approved it in this session.
