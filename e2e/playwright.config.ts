import { defineConfig, devices } from '@playwright/test'

/**
 * Los flujos E2E mutan estado compartido en la base de datos de desarrollo, por
 * lo que se ejecutan de forma serial (workers: 1). El `globalSetup` restablece
 * las placas de prueba antes de cada corrida (`php artisan e2e:reset-fixtures`),
 * de modo que la suite es idempotente sin necesidad de `migrate:fresh`.
 *
 *   - Backend:  `cd backend && php artisan serve` (http://localhost:8000)
 *   - Frontend: `cd frontend && npm run dev` (http://localhost:5173, proxy a :8000)
 */
export default defineConfig({
  globalSetup: './global-setup.ts',
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: [['list']],
  use: {
    baseURL: 'http://localhost:5173',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
})
