import { expect, type Page, test } from '@playwright/test'

import {
  createTicket,
  leaderThen,
  login,
  openSeedList,
  waitForPanel
} from '../support/glpi'

const CONFIG = '/plugins/ticketboost/front/config.form.php'

/** Opens the administration page and fills the fields that are given, then saves. */
async function saveConfig(
  page: Page,
  { closeMode, keys }: { closeMode?: string; keys?: Record<string, string> }
): Promise<void> {
  await page.goto(CONFIG)
  if (closeMode !== undefined) {
    await page.locator(`input[name="close_mode"][value="${closeMode}"]`).check()
  }
  for (const [action, text] of Object.entries(keys ?? {})) {
    await page.locator(`#tb-key-${action}`).fill(text)
  }
  await page.locator('button[name="update"]').click()
  await expect(
    page.getByText('Item successfully updated').first()
  ).toBeVisible()
}

/** Puts the settings back to their defaults through the page itself. */
async function resetConfig(page: Page): Promise<void> {
  await saveConfig(page, { closeMode: 'quick_solution', keys: { next: '' } })
}

test('the administration page keeps what is saved', async ({ page }) => {
  await login(page, 'admin')
  try {
    await saveConfig(page, { closeMode: 'direct_close' })

    await page.goto(CONFIG)
    await expect(
      page.locator('input[name="close_mode"][value="direct_close"]')
    ).toBeChecked()
    await expect(page.locator('#tb-leader-key')).toHaveValue('Control')
  } finally {
    await resetConfig(page)
  }
})

test('with direct close, C closes the ticket without asking for a solution', async ({
  page
}) => {
  await login(page, 'admin')
  const id = await createTicket(page, 'direct close')
  try {
    await saveConfig(page, { closeMode: 'direct_close' })
    await page.goto(`/front/ticket.form.php?id=${String(id)}`)

    await leaderThen(page, 'c')

    await expect(page.getByText('The ticket was closed').first()).toBeVisible()
    await expect(page.locator('.tb-text__input')).toHaveCount(0)
  } finally {
    await resetConfig(page)
  }
})

test('the Record button fills the field with the next shortcut pressed', async ({
  page
}) => {
  await login(page, 'admin')
  await page.goto(CONFIG)

  await page.locator('[data-tb-action="next"] [data-tb-record]').click()
  await expect(
    page.locator('[data-tb-action="next"] [data-tb-feedback]')
  ).toHaveText('Press the new shortcut')
  await page.keyboard.press('Control+Shift+K')

  await expect(page.locator('#tb-key-next')).toHaveValue('Ctrl+Shift+KeyK')
  await expect(
    page.locator('[data-tb-action="next"] [data-tb-feedback]')
  ).toHaveText('')
})

test('a shortcut already in use is flagged as it is typed and refused by the server', async ({
  page
}) => {
  await login(page, 'admin')
  await page.goto(CONFIG)

  await page.locator('#tb-key-next').fill('KeyP')
  await expect(
    page.locator('[data-tb-action="next"] [data-tb-feedback]')
  ).toHaveText('KeyP is already used by Previous ticket')

  // The server checks again, so a browser without the script cannot store it.
  await page.locator('button[name="update"]').click()
  await expect(page.locator('[data-tb-issues]')).toContainText(
    'The settings were not saved'
  )
  await expect(page.locator('[data-tb-issues]')).toContainText(
    'KeyP is already used by'
  )
  await expect(page.locator('#tb-key-next')).toHaveValue('KeyP')

  await page.goto(CONFIG)
  await expect(page.locator('#tb-key-next')).toHaveValue('')
})

test('a shortcut saved by the administrator works in the panel', async ({
  page
}) => {
  await login(page, 'admin')
  const seed = await openSeedList(page)
  try {
    await saveConfig(page, { keys: { next: 'KeyJ' } })
    await page.goto(`/front/ticket.form.php?id=${String(seed[2])}`)
    await waitForPanel(page)

    await leaderThen(page, 'j')

    await page.waitForURL(
      (url) =>
        url.pathname.endsWith('ticket.form.php') &&
        url.searchParams.get('id') !== String(seed[2])
    )
  } finally {
    await resetConfig(page)
  }
})

test('a user without the configure right cannot open the page', async ({
  page
}) => {
  await login(page, 'selfservice')

  const response = await page.goto(CONFIG)

  expect(response?.status()).toBe(403)
})
