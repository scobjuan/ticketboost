import { defineConfig } from 'vitest/config'

export default defineConfig({
  test: {
    environment: 'happy-dom',
    include: ['frontend/tests/**/*.test.ts', 'scripts/tests/**/*.test.mjs'],
    coverage: {
      provider: 'v8',
      include: ['frontend/src/**/*.ts'],
      exclude: ['frontend/src/main.ts'],
      reporter: ['text', 'lcov'],
      thresholds: {
        lines: 90,
        branches: 90,
        functions: 90,
        statements: 90
      }
    }
  }
})
