---
id: 0015
title: Translations with gettext and regional copies
status: accepted
date: 2026-11-09
---

# 0015. Translations with gettext and regional copies

## Context

User-facing text must exist in English and Spanish and follow the GLPI user language, whatever Spanish or English variant is selected.

## Decision

Gettext domain `ticketboost` with `en_GB.po` and `es_ES.po` as sources. `scripts/locales-compile.sh` builds `.mo` files and copies them to en_US and to es_419, es_AR, es_CL, es_CO, es_EC, es_MX, es_VE. The frontend receives translated labels from PHP; no text is hardcoded in TypeScript.

## Alternatives considered

Per-variant translations; translating in TypeScript.

## Consequences

The guard fails when an es_ES msgid is untranslated. `.mo` files are build outputs.
