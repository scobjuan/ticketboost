import { expect, type Page, test } from '@playwright/test'

import { leaderThen, login, openSeedList, waitForPanel } from '../support/glpi'

const ACTION = '/plugins/ticketboost/ajax/action.php'
const SEARCH = '/plugins/ticketboost/ajax/search.php'
const AJAX = { 'X-Requested-With': 'XMLHttpRequest' }

/** The seeded ticket nobody works on (it is requested by the self-service account). */
const FREE_TICKET = 6

async function openFreeTicket(page: Page): Promise<number> {
  await login(page, 'admin')
  const seed = await openSeedList(page)
  const id = seed[FREE_TICKET]
  expect(id).toBeDefined()
  await page.goto(`/front/ticket.form.php?id=${String(id)}`)
  return id!
}

/** Opens "my tickets" and checks whether the free seeded ticket is in the list. */
async function expectUnderMyTickets(
  page: Page,
  listed: boolean
): Promise<void> {
  await leaderThen(page, 'm')
  await page.waitForURL('**/front/ticket.php*')
  const row = page
    .locator('a[href*="ticket.form.php?id="]')
    .filter({ hasText: `[tb-seed] ${String(FREE_TICKET)}` })
  if (listed) {
    await expect(row.first()).toBeVisible()
    return
  }
  // The list is filled after the page loads: wait for the search to settle first.
  await page.waitForLoadState('networkidle')
  await expect(row).toHaveCount(0)
}

test('A assigns the ticket to me and U takes it back', async ({ page }) => {
  const id = await openFreeTicket(page)

  await leaderThen(page, 'a')
  await expect(
    page.getByText('The ticket is now assigned to you').first()
  ).toBeVisible()
  await expectUnderMyTickets(page, true)

  await page.goto(`/front/ticket.form.php?id=${String(id)}`)
  await leaderThen(page, 'a')
  await expect(page.locator('.tb-toast--error')).toHaveText(
    'You are already assigned to this ticket'
  )

  await leaderThen(page, 'u')
  await expect(
    page.getByText('You are no longer assigned to the ticket').first()
  ).toBeVisible()
  await expectUnderMyTickets(page, false)
})

test('O assigns another technician and X removes them', async ({
  page,
  browser,
  baseURL
}) => {
  const id = await openFreeTicket(page)

  await leaderThen(page, 'o')
  await page.locator('.tb-picker__input').fill('tech1')
  await expect(page.locator('.tb-option')).toHaveCount(1)
  await expect(page.locator('.tb-option')).toContainText('tech1')
  await page.keyboard.press('Enter')
  await expect(page.getByText('The ticket was assigned').first()).toBeVisible()

  const tech = await browser.newContext({ baseURL: baseURL ?? '' })
  const techPage = await tech.newPage()
  await login(techPage, 'tech1')
  await expectUnderMyTickets(techPage, true)
  await tech.close()

  await page.goto(`/front/ticket.form.php?id=${String(id)}`)
  await leaderThen(page, 'x')
  await expect(page.locator('.tb-option', { hasText: 'tech1' })).toBeVisible()
  await page.keyboard.press('Enter')
  await expect(page.locator('.tb-option[aria-selected="true"]')).toHaveCount(1)
  await page.keyboard.press('Control+Enter')
  await expect(
    page.getByText('The selected assignees were removed').first()
  ).toBeVisible()

  const left = await page.request.get(
    `${SEARCH}?action=unassign_others&id=${String(id)}`,
    { headers: AJAX }
  )
  expect(((await left.json()) as { data: unknown[] }).data).toEqual([])
})

test('O assigns a group and X removes it', async ({ page }) => {
  const id = await openFreeTicket(page)

  await leaderThen(page, 'o')
  await page.locator('.tb-picker__input').fill('tb-seed] Team')
  await expect(page.locator('.tb-option')).toHaveCount(1)
  await expect(page.locator('.tb-option')).toContainText('[tb-seed] Team')
  await expect(page.locator('.tb-option__detail')).toHaveText('Group')
  await page.keyboard.press('Enter')
  await expect(page.getByText('The ticket was assigned').first()).toBeVisible()

  await page.goto(`/front/ticket.form.php?id=${String(id)}`)
  await leaderThen(page, 'x')
  await expect(
    page.locator('.tb-option', { hasText: '[tb-seed] Team' })
  ).toBeVisible()
  await page.keyboard.press('Enter')
  await expect(page.locator('.tb-option[aria-selected="true"]')).toHaveCount(1)
  await page.keyboard.press('Control+Enter')
  await expect(
    page.getByText('The selected assignees were removed').first()
  ).toBeVisible()

  const left = await page.request.get(
    `${SEARCH}?action=unassign_others&id=${String(id)}`,
    { headers: AJAX }
  )
  expect(((await left.json()) as { data: unknown[] }).data).toEqual([])
})

test('a POST that fails the CSRF protection is refused and changes nothing', async ({
  page
}, testInfo) => {
  const id = await openFreeTicket(page)
  const body = JSON.stringify({
    action: 'assign_other',
    id,
    value: 'user:1'
  })
  const headers = { ...AJAX, 'Content-Type': 'application/json' }

  const crossSite = await page.request.post(ACTION, {
    headers: {
      ...headers,
      'Sec-Fetch-Site': 'cross-site',
      Origin: 'http://evil.example'
    },
    data: body
  })
  expect(crossSite.status()).toBe(403)

  if (testInfo.project.name.startsWith('glpi11')) {
    // GLPI 11 also needs its session token; without it the request never reaches the plugin.
    const withoutToken = await page.request.post(ACTION, {
      headers,
      data: body
    })
    expect(withoutToken.status()).toBe(403)
  }

  const after = await page.request.get(
    `${SEARCH}?action=unassign_others&id=${String(id)}`,
    { headers: AJAX }
  )
  expect(((await after.json()) as { data: unknown[] }).data).toEqual([])
})

test('self-service users get no assignment actions and are refused by the server', async ({
  page
}) => {
  await login(page, 'selfservice')
  await waitForPanel(page)
  await page.keyboard.press('Control')
  await expect(page.locator('.tb-modal')).toBeVisible()
  await expect(page.locator('.tb-modal')).not.toContainText('Assign to me')
  await expect(page.locator('.tb-modal')).not.toContainText('Unassign')
  await page.keyboard.press('Escape')

  const search = await page.request.get(`${SEARCH}?action=assign_other&id=1`, {
    headers: AJAX
  })
  expect([403, 404]).toContain(search.status())
  const invalid = await page.request.get(`${SEARCH}?action=assign_me&id=1`, {
    headers: AJAX
  })
  expect(invalid.status()).toBe(400)
})

test('a technician without the assign right only gets the actions on themselves', async ({
  page
}) => {
  const id = await openFreeTicket(page)
  await page.context().clearCookies()
  await login(page, 'tech')
  await page.goto(`/front/ticket.form.php?id=${String(id)}`)
  await waitForPanel(page)
  await page.keyboard.press('Control')
  await expect(page.locator('.tb-modal')).toContainText('Assign to me')
  await expect(page.locator('.tb-modal')).not.toContainText(
    'Assign to someone else'
  )
  await page.keyboard.press('Escape')

  const search = await page.request.get(
    `${SEARCH}?action=assign_other&id=${String(id)}`,
    { headers: AJAX }
  )
  expect(search.status()).toBe(403)
})
