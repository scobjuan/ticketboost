import { expect, test } from '@playwright/test'

import { leaderThen, login, openSeedList, waitForPanel } from '../support/glpi'

const NAVIGATE = '/plugins/ticketboost/ajax/navigate.php'
const AJAX = { 'X-Requested-With': 'XMLHttpRequest' }

test('N and P walk the result list and wrap around at both ends', async ({
  page
}) => {
  await login(page, 'admin')
  const seed = await openSeedList(page)
  expect(Object.keys(seed)).toHaveLength(6)

  await page
    .locator(`a[href*="id=${String(seed[1])}"]`)
    .first()
    .click()
  await page.waitForURL(`**/ticket.form.php?id=${String(seed[1])}*`)

  await leaderThen(page, 'n')
  await page.waitForURL(`**/ticket.form.php?id=${String(seed[2])}*`)

  await leaderThen(page, 'ArrowRight')
  await page.waitForURL(`**/ticket.form.php?id=${String(seed[3])}*`)

  await leaderThen(page, 'p')
  await page.waitForURL(`**/ticket.form.php?id=${String(seed[2])}*`)

  await leaderThen(page, 'ArrowLeft')
  await page.waitForURL(`**/ticket.form.php?id=${String(seed[1])}*`)

  await leaderThen(page, 'p')
  await page.waitForURL(`**/ticket.form.php?id=${String(seed[6])}*`)

  await leaderThen(page, 'n')
  await page.waitForURL(`**/ticket.form.php?id=${String(seed[1])}*`)
})

test('L goes back to the ticket list', async ({ page }) => {
  await login(page, 'admin')
  const seed = await openSeedList(page)
  await page.goto(`/front/ticket.form.php?id=${String(seed[2])}`)

  await leaderThen(page, 'l')

  await page.waitForURL('**/front/ticket.php*')
})

test('M lists the tickets assigned to a technician', async ({
  page,
  browser,
  baseURL
}) => {
  await login(page, 'admin')
  await openSeedList(page)
  const tech = await browser.newContext({ baseURL: baseURL ?? '' })
  const techPage = await tech.newPage()
  await login(techPage, 'tech1')
  await waitForPanel(techPage)

  await leaderThen(techPage, 'm')

  await techPage.waitForURL('**/front/ticket.php*')
  const rows = techPage.locator('a[href*="ticket.form.php?id="]')
  await expect(rows.filter({ hasText: '[tb-seed] 1' }).first()).toBeVisible()
  await expect(rows.filter({ hasText: '[tb-seed] 2' }).first()).toBeVisible()
  await expect(rows.filter({ hasText: '[tb-seed] 3' }).first()).toBeVisible()
  await expect(rows.filter({ hasText: '[tb-seed] 4' })).toHaveCount(0)
  await expect(rows.filter({ hasText: '[tb-seed] 6' })).toHaveCount(0)
  await tech.close()
})

test('M lists the tickets requested by a self-service user', async ({
  page
}) => {
  await login(page, 'selfservice')
  await waitForPanel(page)

  await leaderThen(page, 'm')

  await page.waitForURL('**/front/ticket.php*')
  const rows = page.locator('a[href*="ticket.form.php?id="]')
  await expect(rows.filter({ hasText: '[tb-seed] 1' }).first()).toBeVisible()
  await expect(rows.filter({ hasText: '[tb-seed] 4' }).first()).toBeVisible()
  await expect(rows.filter({ hasText: '[tb-seed] 6' }).first()).toBeVisible()
  await expect(rows.filter({ hasText: '[tb-seed] 2' })).toHaveCount(0)
  await expect(rows.filter({ hasText: '[tb-seed] 5' })).toHaveCount(0)
})

test('without a result list N walks the unresolved tickets of a technician', async ({
  page,
  browser,
  baseURL
}) => {
  await login(page, 'admin')
  const seed = await openSeedList(page)

  const fresh = await browser.newContext({ baseURL: baseURL ?? '' })
  const freshPage = await fresh.newPage()
  await login(freshPage, 'tech1')
  await freshPage.goto(`/front/ticket.form.php?id=${String(seed[2])}`)

  await leaderThen(freshPage, 'n')

  await freshPage.waitForURL(`**/ticket.form.php?id=${String(seed[3])}*`)
  await fresh.close()
})

test('a self-service user only walks their own tickets', async ({
  page,
  browser,
  baseURL
}) => {
  await login(page, 'admin')
  const seed = await openSeedList(page)

  const own = await browser.newContext({ baseURL: baseURL ?? '' })
  const ownPage = await own.newPage()
  await login(ownPage, 'selfservice')
  await ownPage.goto(`/front/ticket.form.php?id=${String(seed[1])}`)

  // The self-service home page leaves GLPI's own list in the session, and tickets that share
  // a modification date come out of it in no fixed order, so the walk is checked as a set.
  const ticketId = (url: URL): number => Number(url.searchParams.get('id'))
  const walked: number[] = []
  let current = seed[1]
  for (let step = 0; step < 3; step++) {
    const from = current
    await leaderThen(ownPage, 'n')
    await ownPage.waitForURL(
      (url) =>
        url.pathname.endsWith('ticket.form.php') && ticketId(url) !== from
    )
    current = ticketId(new URL(ownPage.url()))
    walked.push(current)
  }

  expect(new Set(walked)).toEqual(new Set([seed[1], seed[4], seed[6]]))
  expect(walked[2]).toBe(seed[1])
  await own.close()
})

test('the navigation endpoint answers with the address to open', async ({
  page
}) => {
  await login(page, 'tech')

  const response = await page.request.get(`${NAVIGATE}?to=list`, {
    headers: AJAX
  })

  expect(response.status()).toBe(200)
  const body = (await response.json()) as { ok: boolean; redirect: string }
  expect(body.ok).toBe(true)
  expect(body.redirect).toContain('/front/ticket.php')
})

test('the navigation endpoint refuses invalid requests', async ({
  page,
  playwright,
  baseURL
}) => {
  await login(page, 'tech')

  for (const query of [
    'to=delete',
    'to=next',
    'to=list&extra=1',
    'to=next&id=0'
  ]) {
    const response = await page.request.get(`${NAVIGATE}?${query}`, {
      headers: AJAX
    })
    expect(response.status(), query).toBe(400)
    expect(((await response.json()) as { ok: boolean }).ok).toBe(false)
  }

  const plain = await page.request.get(`${NAVIGATE}?to=list`)
  expect(plain.status()).toBe(400)

  const anonymous = await playwright.request.newContext({
    baseURL: baseURL ?? ''
  })
  const denied = await anonymous.get(`${NAVIGATE}?to=list`, { headers: AJAX })
  expect(denied.status()).toBe(401)
  await anonymous.dispose()
})
