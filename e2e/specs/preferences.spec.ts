import { expect, type Page, test } from '@playwright/test'

import { leaderThen, login, openSeedList, waitForPanel } from '../support/glpi'

const PREFERENCE_FORM = '/plugins/ticketboost/front/preference.form.php'
/** GLPI keeps other forms, with their own Save button, on the same page. */
const FORM = 'form[data-tb-config]'

/** Opens "My settings" on the TicketBoost tab, whose content GLPI loads by AJAX. */
async function openPreferences(page: Page): Promise<void> {
  await page.goto('/front/preference.php')
  await page
    .locator('a[data-bs-toggle="tab"]')
    .filter({ hasText: 'TicketBoost' })
    .first()
    .click()
  await expect(page.locator(FORM)).toBeVisible()
}

/** Saves the open form and waits for GLPI to confirm it. */
async function save(page: Page): Promise<void> {
  await page.locator(`${FORM} button[name="update"]`).click()
  await expect(
    page.getByText('Item successfully updated').first()
  ).toBeVisible()
}

/** Puts the personal settings back to their defaults through the tab itself. */
async function restore(page: Page): Promise<void> {
  await openPreferences(page)
  await page.locator(`${FORM} button[name="restore"]`).click()
  await expect(
    page.getByText('Item successfully updated').first()
  ).toBeVisible()
}

test('a shortcut recorded in the personal settings works in the panel', async ({
  page
}) => {
  await login(page, 'tech1')
  const seed = await openSeedList(page)
  try {
    await openPreferences(page)
    await page.locator('[data-tb-action="next"] [data-tb-record]').click()
    await page.keyboard.press('j')
    await expect(page.locator('#tb-key-next')).toHaveValue('KeyJ')
    await save(page)
    await openSeedList(page)
    await page.goto(`/front/ticket.form.php?id=${String(seed[2])}`)

    await leaderThen(page, 'j')

    await page.waitForURL(
      (url) =>
        url.pathname.endsWith('ticket.form.php') &&
        url.searchParams.get('id') !== String(seed[2])
    )
  } finally {
    await restore(page)
  }
})

test('turning the shortcuts off stops the panel until the defaults are restored', async ({
  page
}) => {
  await login(page, 'tech1')
  try {
    await openPreferences(page)
    await page.locator(`${FORM} input[name="enabled"]`).uncheck()
    await save(page)

    await page.goto('/front/ticket.php')
    await expect(page.locator('html')).toHaveAttribute(
      'data-ticketboost-panel',
      'off'
    )
  } finally {
    await restore(page)
  }
  await page.goto('/front/ticket.php')
  await waitForPanel(page)
})

test('a disabled action disappears from the panel', async ({ page }) => {
  await login(page, 'tech1')
  try {
    await openPreferences(page)
    await page.locator('input[name="disabled[my_tickets]"]').check()
    await expect(page.locator('#tb-key-my_tickets')).toBeDisabled()
    await save(page)
    await page.goto('/front/ticket.php')

    await leaderThen(page, '?')

    await expect(page.locator('.tb-modal')).toContainText('Show all shortcuts')
    await expect(page.locator('.tb-modal')).not.toContainText('My tickets')
  } finally {
    await restore(page)
  }
})

test('a personal shortcut already in use is flagged and refused', async ({
  page
}) => {
  await login(page, 'tech1')
  await openPreferences(page)

  await page.locator('#tb-key-next').fill('KeyP')
  await expect(
    page.locator('[data-tb-action="next"] [data-tb-feedback]')
  ).toHaveText('KeyP is already used by Previous ticket')

  await page.locator(`${FORM} button[name="update"]`).click()
  await expect(page.locator('[data-tb-issues]')).toContainText(
    'KeyP is already used by'
  )
  await expect(page.locator('#tb-key-next')).toHaveValue('KeyP')

  await openPreferences(page)
  await expect(page.locator('#tb-key-next')).toHaveValue('')
})

test('a self-service user changes their own leader key', async ({ page }) => {
  await login(page, 'selfservice')
  try {
    await openPreferences(page)
    await page.locator('#tb-leader-key').selectOption('Alt')
    await save(page)
    await page.reload()
    await waitForPanel(page)

    await page.keyboard.press('Alt')

    await expect(page.locator('.tb-modal')).toBeVisible()
  } finally {
    await restore(page)
  }
})

test('the personal settings and the panel follow a Spanish user', async ({
  page
}) => {
  await login(page, 'spanish')
  await openPreferences(page)

  await expect(page.locator(`${FORM} button[name="restore"]`)).toHaveText(
    'Restaurar valores por defecto'
  )
  await page.locator('#tb-key-next').fill('KeyP')
  await expect(
    page.locator('[data-tb-action="next"] [data-tb-feedback]')
  ).toContainText('KeyP ya')

  await page.goto('/front/ticket.php')
  await leaderThen(page, '?')
  await expect(page.locator('.tb-modal')).toContainText('Mis tickets')
})

test('a technician cannot open the settings of another user', async ({
  page
}) => {
  await login(page, 'tech1')

  const response = await page.goto(`${PREFERENCE_FORM}?users_id=2`)

  expect(response?.status()).toBe(403)
})
