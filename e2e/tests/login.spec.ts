import { test, expect } from '@playwright/test'
import { login, CREDENCIALES } from './fixtures'

/** Módulos visibles en la sidebar según el permiso que los habilita. */
const NAV = {
  dashboard: 'Dashboard',
  cargue: 'Cargue Masivo',
  inventario: 'Inventario',
  equipos: 'Equipos',
  soplado: 'Soplado',
  portatiles: 'Portátiles',
  trazabilidad: 'Trazabilidad',
} as const

test.describe('Login y redirección por permisos', () => {
  test('redirige a /login sin sesión y a /dashboard tras autenticarse', async ({ page }) => {
    await page.goto('/dashboard')
    await expect(page).toHaveURL(/\/login$/)

    await login(page, CREDENCIALES.cpu.email)
    await expect(page).toHaveURL(/\/dashboard$/)
    await expect(page.getByRole('heading', { name: 'Dashboard de Control Operativo' })).toBeVisible()
  })

  test('el técnico CPU solo ve los módulos de su rol', async ({ page }) => {
    await login(page, CREDENCIALES.cpu.email)

    const nav = page.getByRole('navigation')
    for (const modulo of [NAV.dashboard, NAV.inventario, NAV.equipos, NAV.trazabilidad]) {
      await expect(nav.getByRole('link', { name: modulo })).toBeVisible()
    }

    for (const modulo of [NAV.cargue, NAV.soplado, NAV.portatiles]) {
      await expect(nav.getByRole('link', { name: modulo })).toHaveCount(0)
    }
  })

  test('el administrador ve todos los módulos', async ({ page }) => {
    await login(page, CREDENCIALES.admin.email)

    const nav = page.getByRole('navigation')
    for (const modulo of Object.values(NAV)) {
      await expect(nav.getByRole('link', { name: modulo })).toBeVisible()
    }
  })
})
