import { expect, test } from '@playwright/test'

import { login } from '../support/glpi'

test('the plugin bundle runs for an authenticated technician', async ({
  page
}) => {
  await login(page, 'tech')

  await expect(page.locator('html')).toHaveAttribute(
    'data-ticketboost',
    'ready'
  )
})

test('the plugin bundle runs in the self-service interface', async ({
  page
}) => {
  await login(page, 'selfservice')

  await expect(page.locator('html')).toHaveAttribute(
    'data-ticketboost',
    'ready'
  )
})
