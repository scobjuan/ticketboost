---
id: 0009
title: CI on the official GLPI CI image with project-owned workflows
status: accepted
date: 2026-11-09
---

# 0009. CI on the official GLPI CI image with project-owned workflows

## Context

`glpi-project/plugin-ci-workflows` offers reusable workflows, but they require a manual CHANGELOG change in every PR and do not enforce coverage thresholds.

## Decision

Own workflows in `.github/workflows/`. The PHP job runs `scripts/php-ci.sh` inside `ghcr.io/glpi-project/githubactions-glpi-apache:php-X-glpi-Y` with the matching `githubactions-mariadb`/`mysql` service; the same script runs locally via `make test-php`.

## Alternatives considered

Use the reusable workflow as is.

## Consequences

Matrix: GLPI 11.0.x on PHP 8.2 and 8.5, GLPI 12.0.x on PHP 8.3 and 8.5. Review the official matrix when GLPI releases a new version.
