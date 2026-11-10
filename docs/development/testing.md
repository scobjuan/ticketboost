# Testing

Tests are a merge requirement: CI blocks any pull request with a failing test or with
coverage below the thresholds.

| Layer                    | Tool                                             | Location                          | Command         | Gate                                       |
| ------------------------ | ------------------------------------------------ | --------------------------------- | --------------- | ------------------------------------------ |
| TypeScript unit          | Vitest + happy-dom                               | `frontend/tests/`                 | `make test-ts`  | 90% lines, branches, functions, statements |
| PHP unit and integration | PHPUnit (GLPI test framework)                    | `tests/Unit`, `tests/Integration` | `make test-php` | 85% lines                                  |
| End to end               | Playwright                                       | `e2e/specs/`                      | `make e2e`      | All specs on GLPI 11 and 12                |
| Static analysis          | ESLint, tsc, PHPStan (max), Rector, php-cs-fixer | -                                 | `make lint`     | No errors                                  |

## PHP tests

PHP tests run inside `ghcr.io/glpi-project/githubactions-glpi-apache`, a GLPI development
checkout with PHPUnit, PHPStan and pcov. `scripts/php-ci.sh` installs a test database,
installs and activates the plugin, runs PHPUnit with coverage and enforces the minimum
with `tools/coverage-check.php`. The same script runs locally and in CI.

## End-to-end tests

Playwright projects combine a GLPI version and a browser: `glpi11-chromium`,
`glpi12-chromium`, `glpi12-firefox`, `glpi12-webkit`. Run one with
`pnpm e2e --project=glpi11-chromium`. Helpers live in `e2e/support/`.

## Rules

- Every change in `src/` or `frontend/src/` comes with tests.
- A bug fix starts with a test that fails without the fix.
- Wait for state, never for time; no retries to hide flaky tests.
