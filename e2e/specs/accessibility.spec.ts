import { AxeBuilder } from '@axe-core/playwright'
import { expect, type Page, test } from '@playwright/test'

import {
  createTicket,
  leaderThen,
  login,
  openSeedList,
  waitForPanel
} from '../support/glpi'

/** Success criteria of WCAG 2.1 levels A and AA. */
const STANDARD = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']

/**
 * Checks one part of the page that TicketBoost draws; GLPI's own markup is not ours to fix.
 * Waits for the entry animation, which would otherwise lower the contrast being measured.
 * @param page Playwright page.
 * @param selector Part of the page to check.
 * @param glpiStyled Controls inside it whose colours come from GLPI alone.
 */
async function expectAccessible(
  page: Page,
  selector: string,
  glpiStyled: string[] = []
): Promise<void> {
  await expect(page.locator(selector).first()).toBeVisible()
  await page
    .locator(selector)
    .first()
    .evaluate((element) =>
      Promise.all(
        element.getAnimations({ subtree: true }).map((a) => a.finished)
      )
    )

  const builder = new AxeBuilder({ page }).include(selector).withTags(STANDARD)
  for (const excluded of glpiStyled) {
    builder.exclude(excluded)
  }
  const { violations } = await builder.analyze()

  expect(
    violations.map(
      ({ id, help, nodes }) =>
        `${id}: ${help} (${nodes.map((n) => n.target.join(' ')).join(', ')})`
    )
  ).toEqual([])
}

test('the shortcut menu, its overview and a submenu are accessible', async ({
  page
}) => {
  await login(page, 'tech1')
  const seed = await openSeedList(page)
  await page.goto(`/front/ticket.form.php?id=${String(seed[1])}`)
  await waitForPanel(page)

  await page.keyboard.press('Control')
  await expectAccessible(page, '.tb-modal')

  await page.keyboard.press('?')
  await expect(page.locator('.tb-item--static').first()).toBeVisible()
  await expectAccessible(page, '.tb-modal')

  await page.keyboard.press('Escape')
  await page.keyboard.press('s')
  await expect(page.locator('.tb-modal__title')).toContainText('/')
  await expectAccessible(page, '.tb-modal')
})

test('the assignee picker is accessible with results and without', async ({
  page
}) => {
  await login(page, 'admin')
  const id = await createTicket(page, 'accessibility picker')
  await page.goto(`/front/ticket.form.php?id=${String(id)}`)
  await leaderThen(page, 'o')
  await page.locator('.tb-picker__input').fill('tech')
  await expect(page.locator('.tb-option').first()).toBeVisible()
  await expectAccessible(page, '.tb-modal')

  await page.locator('.tb-picker__input').fill('nobody-has-this-name')
  await expect(page.locator('.tb-picker__empty')).toBeVisible()
  await expectAccessible(page, '.tb-modal')
})

test('the solution box and a message are accessible', async ({ page }) => {
  await login(page, 'admin')
  const id = await createTicket(page, 'accessibility solution')
  await page.goto(`/front/ticket.form.php?id=${String(id)}`)
  await leaderThen(page, 'c')
  await expectAccessible(page, '.tb-modal')
  await page.keyboard.press('Escape')

  await page.route('**/ticketboost/ajax/navigate.php*', (route) =>
    route.fulfill({
      status: 404,
      json: { ok: false, message: 'There is no other ticket', data: {} }
    })
  )
  await leaderThen(page, 'n')
  await expectAccessible(page, '.tb-toast')
})

test('the panel keeps the focus inside and gives it back on close', async ({
  page
}) => {
  await login(page, 'tech1')
  await page.goto('/front/ticket.php')
  await waitForPanel(page)
  const origin = page.locator('a[href]:visible').first()
  await origin.focus()

  await page.keyboard.press('Control')
  const inside = (): Promise<boolean> =>
    page.evaluate(() => document.activeElement?.closest('.tb-modal') !== null)
  for (let presses = 0; presses < 8; presses += 1) {
    await page.keyboard.press('Tab')
    expect(await inside()).toBe(true)
  }
  await page.keyboard.press('Escape')

  await expect(origin).toBeFocused()
})

test('the panel does not animate when reduced motion is asked for', async ({
  page
}) => {
  await page.emulateMedia({ reducedMotion: 'reduce' })
  await login(page, 'tech1')
  await page.goto('/front/ticket.php')
  await waitForPanel(page)

  await page.keyboard.press('Control')

  await expect(page.locator('.tb-modal')).toHaveCSS('animation-name', 'none')
})

test('the administration page is accessible', async ({ page }) => {
  await login(page, 'admin')
  await page.goto('/plugins/ticketboost/front/config.form.php')
  await expectAccessible(page, 'form[data-tb-config]')

  await page.locator('#tb-key-next').fill('KeyP')
  await expectAccessible(page, 'form[data-tb-config]')
})

test('the personal settings are accessible', async ({ page }) => {
  await login(page, 'selfservice')
  await page.goto('/plugins/ticketboost/front/preference.form.php')
  await expectAccessible(page, 'form[data-tb-config]')

  await page.locator('#tb-key-next').fill('KeyP')
  await page.locator('input[name="disabled[close]"]').check()
  await expectAccessible(page, 'form[data-tb-config]')
})

test('the panel and the settings stay readable with a dark palette', async ({
  page
}) => {
  await login(page, 'dark')
  await page.goto('/plugins/ticketboost/front/preference.form.php')
  await expect(page.locator('html')).toHaveAttribute(
    'data-glpi-theme',
    'darker'
  )
  // The primary button is GLPI's own; the darker palette gives it too little contrast.
  await expectAccessible(page, 'form[data-tb-config]', ['.btn-primary'])

  await waitForPanel(page)
  await page.keyboard.press('Control')
  await expectAccessible(page, '.tb-modal')
})
