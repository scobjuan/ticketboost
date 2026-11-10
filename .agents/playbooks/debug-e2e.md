---
title: Debug a failing e2e test
when: A Playwright spec fails locally or in CI.
---

# Debug a failing e2e test

1. CI: download the `playwright-report` artifact and open the trace
   (`pnpm exec playwright show-trace trace.zip`).
2. Locally: `make up`, `make install-plugin`, then
   `pnpm e2e --project=glpi11-chromium e2e/specs/<file> --debug` or `make e2e-ui`.
3. Check the plugin is active: `podman-compose exec glpi11 php bin/console plugin:list`.
4. Check the bundle loaded: `<html data-ticketboost="ready">` in the page.
5. GLPI logs: `podman-compose exec glpi11 tail -n 100 /var/glpi/logs/php-errors.log`.
6. Prefer role and label selectors; GLPI core e2e page objects (`tests/e2e/pages` in
   glpi-project/glpi) are a good reference.
7. Flaky test: fix the cause (wait for state, not time). Never add retries or sleeps to hide it.
8. Record the root cause in KNOWLEDGE "Testing learnings" if it can happen again.
