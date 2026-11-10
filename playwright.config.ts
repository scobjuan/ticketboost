import { defineConfig, devices } from '@playwright/test'

// One project per GLPI version and browser. Run a subset with --project, e.g.
//   pnpm e2e --project=glpi11-chromium
const GLPI11_URL = process.env.GLPI11_URL ?? 'http://localhost:8011'
const GLPI12_URL = process.env.GLPI12_URL ?? 'http://localhost:8012'

export default defineConfig({
  testDir: 'e2e/specs',
  timeout: 60_000,
  fullyParallel: false,
  workers: 1,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
  use: {
    trace: 'retain-on-failure',
    video: 'retain-on-failure',
    screenshot: 'only-on-failure'
  },
  projects: [
    {
      name: 'glpi11-chromium',
      use: { ...devices['Desktop Chrome'], baseURL: GLPI11_URL }
    },
    {
      name: 'glpi12-chromium',
      use: { ...devices['Desktop Chrome'], baseURL: GLPI12_URL }
    },
    {
      name: 'glpi12-firefox',
      use: { ...devices['Desktop Firefox'], baseURL: GLPI12_URL }
    },
    {
      name: 'glpi12-webkit',
      use: { ...devices['Desktop Safari'], baseURL: GLPI12_URL }
    }
  ]
})
