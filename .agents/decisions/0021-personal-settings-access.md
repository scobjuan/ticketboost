---
id: 0021
title: Who may change the personal settings of a user
status: accepted
date: 2026-11-25
---

# 0021. Who may change the personal settings of a user

## Context

PLAN.md section 3.5 puts the personal settings on a tab of the Preference and User pages but
does not say who may change them on the User page, where an administrator opens other users.

## Decision

`PreferenceTab::canManage()` is the single rule, used by the tab and by
`front/preference.form.php`:

- own settings: the `plugin_ticketboost` READ right (use);
- another user: the `plugin_ticketboost` UPDATE right (configure), the native right to update
  that user, and that user may not have more rights than the editor
  (`User::currentUserHaveMoreRightThan`).

The tab is a separate class (`PreferenceTab`), like `ProfileTab`, and `Preference` stays the
storage class.

## Alternatives considered

- Own settings only: an administrator could not help a user who locked a key.
- The native right to update the user alone: lets people who cannot configure the plugin
  change it for others.

## Consequences

Self-service users manage their own settings. Delegated user administrators need the plugin
configure right to touch the settings of others.
