import { expect, type Page } from '@playwright/test'

/** Default accounts created by the GLPI installer. */
export const USERS = {
  admin: { login: 'glpi', password: 'glpi' },
  tech: { login: 'tech', password: 'tech' },
  tech1: { login: 'tech1', password: 'tech1' },
  tech2: { login: 'tech2', password: 'tech2' },
  normal: { login: 'normal', password: 'normal' },
  selfservice: { login: 'post-only', password: 'postonly' }
} as const

export type UserKey = keyof typeof USERS

/**
 * Logs in through the GLPI login form and waits for the authenticated layout.
 * @param page Playwright page.
 * @param user Account to use.
 */
export async function login(page: Page, user: UserKey): Promise<void> {
  const { login: name, password } = USERS[user]
  await page.goto('/')
  await page.locator('input[name="login_name"], #login_name').first().fill(name)
  await page.locator('input[type="password"]').first().fill(password)
  await page.locator('button[type="submit"], [name="submit"]').first().click()
  await page.waitForURL(
    (url) => !url.pathname.endsWith('/') && !url.pathname.endsWith('index.php')
  )
  await expect(page.locator('input[type="password"]')).toHaveCount(0)
}

/**
 * Waits until the shortcut panel is running on the current page.
 * @param page Playwright page.
 */
export async function waitForPanel(page: Page): Promise<void> {
  await expect(page.locator('html')).toHaveAttribute(
    'data-ticketboost-panel',
    'ready'
  )
}

/**
 * Taps the leader key and presses an action key.
 * @param page Playwright page.
 * @param key Key of the action, as Playwright names it.
 */
export async function leaderThen(page: Page, key: string): Promise<void> {
  await waitForPanel(page)
  await page.keyboard.press('Control')
  await expect(page.locator('.tb-modal')).toBeVisible()
  await page.keyboard.press(key)
}

/** The seeded tickets, by their number in the title `[tb-seed] N`, to their GLPI id. */
export type SeedTickets = Record<number, number>

/**
 * Opens the ticket list filtered to the seeded tickets, oldest first, and reads their ids.
 * Rendering the list also fills the session list that next and previous walk.
 * @param page Playwright page, logged in.
 * @returns The seeded tickets visible to the user.
 */
export async function openSeedList(page: Page): Promise<SeedTickets> {
  await page.goto(
    '/front/ticket.php?reset=reset&sort[]=2&order[]=ASC' +
      '&criteria[0][field]=1&criteria[0][searchtype]=contains&criteria[0][value]=tb-seed'
  )
  const links = page
    .locator('a[href*="ticket.form.php?id="]')
    .filter({ hasText: '[tb-seed]' })
  await expect(links.first()).toBeVisible()
  const found = await links.evaluateAll((anchors) =>
    anchors.map((a) => ({
      href: (a as HTMLAnchorElement).href,
      text: a.textContent
    }))
  )
  const tickets: SeedTickets = {}
  for (const { href, text } of found) {
    const number = /\[tb-seed\] (\d)/.exec(text)?.[1]
    const id = /id=(\d+)/.exec(href)?.[1]
    if (number !== undefined && id !== undefined) {
      tickets[Number(number)] = Number(id)
    }
  }
  return tickets
}

/**
 * Creates a ticket as the logged-in user through GLPI's own form endpoint, so a spec can
 * change it freely without touching the seeded tickets. Its title starts with `[tb-e2e]`,
 * which the seeded list (`[tb-seed]`) never matches.
 * @param page Playwright page, logged in with a profile that may create tickets.
 * @param title Text that follows the `[tb-e2e]` prefix.
 * @returns The id of the new ticket.
 */
export async function createTicket(page: Page, title: string): Promise<number> {
  await page.goto('/front/ticket.form.php')
  // GLPI 11 publishes a session token; GLPI 12 protects requests without one.
  const meta = page.locator('meta[property="glpi:csrf_token"]')
  const token =
    (await meta.count()) > 0 ? await meta.getAttribute('content') : null
  const response = await page.request.post('/front/ticket.form.php', {
    headers: token === null ? {} : { 'X-Glpi-Csrf-Token': token },
    form: {
      add: '1',
      name: `[tb-e2e] ${title}`,
      content: 'Created by the TicketBoost e2e specs.',
      entities_id: '0',
      itilcategories_id: '0',
      type: '1',
      urgency: '3',
      impact: '3',
      priority: '3',
      ...(token === null ? {} : { _glpi_csrf_token: token })
    },
    maxRedirects: 0
  })
  const id = /id=(\d+)/.exec(response.headers().location ?? '')?.[1]
  expect(
    id,
    `ticket creation answered ${String(response.status())}`
  ).toBeDefined()
  return Number(id)
}
