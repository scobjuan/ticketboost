import { expect, type Page, test } from '@playwright/test'

import {
  createTicket,
  leaderThen,
  login,
  openSeedList,
  waitForPanel
} from '../support/glpi'

/** TinyMCE starts slowly the first time a form opens, above all in WebKit. */
const EDITOR_TIMEOUT = 20_000

/** Logs in as the super-admin and opens a ticket created just for the test. */
async function openNewTicket(page: Page, title: string): Promise<number> {
  await login(page, 'admin')
  const id = await createTicket(page, title)
  await page.goto(`/front/ticket.form.php?id=${String(id)}`)
  return id
}

test('S opens the status submenu and a digit changes the status', async ({
  page
}) => {
  await openNewTicket(page, 'status')

  await leaderThen(page, 's')
  await expect(page.locator('.tb-modal__title')).toContainText('Change status')
  await expect(page.locator('.tb-modal [data-tb-item]')).toHaveCount(4)
  await page.keyboard.press('4')

  await expect(
    page.getByText('The ticket status was changed').first()
  ).toBeVisible()
  await expect(page.locator('select[name="status"]')).toHaveValue('4')
})

test('R opens the priority submenu and a digit changes the priority', async ({
  page
}) => {
  await openNewTicket(page, 'priority')

  await leaderThen(page, 'r')
  await expect(page.locator('.tb-modal [data-tb-item]')).toHaveCount(6)
  await page.keyboard.press('5')

  await expect(
    page.getByText('The ticket priority was changed').first()
  ).toBeVisible()
})

test('C asks for a solution and solves the ticket', async ({ page }) => {
  await openNewTicket(page, 'close')

  await leaderThen(page, 'c')
  await expect(page.locator('.tb-text__input')).toBeFocused({
    timeout: EDITOR_TIMEOUT
  })
  await page.keyboard.press('Control+Enter')
  await expect(page.locator('.tb-modal')).toBeVisible()
  await page.locator('.tb-text__input').fill('Replaced the faulty switch')
  await page.keyboard.press('Control+Enter')

  await expect(
    page.getByText('The solution was added to the ticket').first()
  ).toBeVisible()
  await expect(
    page.getByText('Replaced the faulty switch').first()
  ).toBeVisible()
})

for (const [key, block, text] of [
  ['f', '#new-ITILFollowup-block', 'Called the requester'],
  ['t', '#new-TicketTask-block', 'Check the cabling']
] as const) {
  test(`${key.toUpperCase()} opens the editor with the cursor in it and Ctrl+Enter sends it`, async ({
    page
  }) => {
    await openNewTicket(page, `editor ${key}`)

    await leaderThen(page, key)
    await expect(page.locator(block)).toBeVisible()
    const editor = page
      .frameLocator(`${block} iframe.tox-edit-area__iframe`)
      .locator('body')
    await expect(editor).toBeVisible({ timeout: EDITOR_TIMEOUT })
    // The first time, TinyMCE needs a moment to start; the cursor arrives when it is ready.
    await expect(
      page.locator(`${block} iframe.tox-edit-area__iframe`)
    ).toBeFocused()
    await page.keyboard.type(text)
    await expect(editor).toContainText(text)
    await page.keyboard.press('Control+Enter')

    await expect(page.getByText(text).first()).toBeVisible()
    await expect(page.locator(`${block}.show`)).toHaveCount(0)
  })
}

test('the server refuses ticket actions to a self-service user', async ({
  page
}) => {
  // A seeded ticket the account requested: the actions are refused, so it never changes.
  await login(page, 'selfservice')
  const id = (await openSeedList(page))[1]
  expect(id).toBeDefined()
  await page.goto(`/front/ticket.form.php?id=${String(id)}`)
  await waitForPanel(page)

  await page.keyboard.press('Control')
  await expect(page.locator('.tb-modal')).toBeVisible()
  await expect(page.locator('.tb-modal')).not.toContainText('Change priority')
  await expect(page.locator('.tb-modal')).not.toContainText('Close ticket')
  await page.keyboard.press('Escape')

  const status = await page.evaluate(async (ticket) => {
    const token = document
      .querySelector('meta[property="glpi:csrf_token"]')
      ?.getAttribute('content')
    const response = await fetch('/plugins/ticketboost/ajax/action.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(token ? { 'X-Glpi-Csrf-Token': token } : {})
      },
      body: JSON.stringify({
        action: 'change_priority',
        id: ticket,
        value: '5'
      })
    })
    return response.status
  }, id)
  expect(status).toBe(403)
})
