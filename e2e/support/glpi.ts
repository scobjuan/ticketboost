import { expect, type Page } from '@playwright/test'

/** Default accounts created by the GLPI installer. */
export const USERS = {
  admin: { login: 'glpi', password: 'glpi' },
  tech: { login: 'tech', password: 'tech' },
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
