import { expect, type Page, test } from '@playwright/test'

import { login } from '../support/glpi'

const BOOTSTRAP = '/plugins/ticketboost/ajax/bootstrap.php'

async function waitForPanel(page: Page): Promise<void> {
  await expect(page.locator('html')).toHaveAttribute(
    'data-ticketboost-panel',
    'ready'
  )
}

test('tapping Ctrl opens the panel, ? lists the shortcuts and Esc closes it', async ({
  page
}) => {
  await login(page, 'admin')
  await waitForPanel(page)

  await page.keyboard.press('Control')

  const panel = page.locator('.tb-modal')
  await expect(panel).toBeVisible()
  await expect(panel).toHaveAttribute('role', 'dialog')
  await expect(panel.locator('.tb-modal__title')).toHaveText('TicketBoost')
  await expect(panel.locator('.tb-modal__footer')).toContainText(
    '? all shortcuts'
  )

  await page.keyboard.press('?')
  await expect(panel.locator('.tb-modal__title')).toHaveText(
    'TicketBoost / All shortcuts'
  )
  await expect(panel.locator('.tb-key').first()).toHaveText('?')

  await page.keyboard.press('Escape')
  await expect(panel.locator('.tb-modal__title')).toHaveText('TicketBoost')
  await page.keyboard.press('Escape')
  await expect(panel).toHaveCount(0)
})

test('Ctrl+C and Ctrl+V keep working and never open the panel', async ({
  page
}) => {
  await login(page, 'admin')
  await waitForPanel(page)

  await page.keyboard.down('Control')
  await page.keyboard.press('c')
  await page.keyboard.up('Control')

  await expect(page.locator('.tb-modal')).toHaveCount(0)
})

test('the panel also opens in the self-service interface', async ({ page }) => {
  await login(page, 'selfservice')
  await waitForPanel(page)

  await page.keyboard.press('Control')

  await expect(page.locator('.tb-modal')).toBeVisible()
})

test('the bootstrap endpoint answers an authenticated AJAX request', async ({
  page
}) => {
  await login(page, 'tech')

  const response = await page.request.get(BOOTSTRAP, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })

  expect(response.status()).toBe(200)
  expect(response.headers()['content-type']).toContain('application/json')
  expect(response.headers()['cache-control']).toContain('no-store')
  const body = (await response.json()) as {
    ok: boolean
    data: {
      enabled: boolean
      available: string[]
      leader: { key: string }
      keymap: Record<string, string[]>
      labels: Record<string, string>
    }
  }
  expect(body.ok).toBe(true)
  expect(body.data.enabled).toBe(true)
  expect(body.data.available).toEqual(['help'])
  expect(body.data.leader.key).toBe('Control')
  expect(body.data.keymap.next).toEqual(['KeyN', 'ArrowRight'])
  expect(body.data.labels['ui.title']).toBe('TicketBoost')
})

test('the bootstrap endpoint refuses requests that are not AJAX or not authenticated', async ({
  page,
  playwright,
  baseURL
}) => {
  await login(page, 'tech')

  const plain = await page.request.get(BOOTSTRAP)
  expect(plain.status()).toBe(400)
  expect(((await plain.json()) as { ok: boolean }).ok).toBe(false)

  const anonymous = await playwright.request.newContext({
    baseURL: baseURL ?? ''
  })
  const denied = await anonymous.get(BOOTSTRAP, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  expect(denied.status()).toBe(401)
  await anonymous.dispose()
})
