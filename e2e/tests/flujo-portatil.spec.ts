import { test, expect } from '@playwright/test'
import { login, escanear, PLACAS, CREDENCIALES } from './fixtures'

test.describe('Flujo Portátil: garantía → cierre único', () => {
  test('GARANTIA FABRICA → PENDIENTE_GARANTIA → cierre → INTERVENIDO', async ({ page }) => {
    await login(page, CREDENCIALES.admin.email)
    await page.getByRole('link', { name: 'Portátiles' }).click()
    await page.waitForURL('**/portatiles')

    // 1) Diagnóstico nuevo: Garantía / Fábrica → registro único abierto.
    await escanear(page, PLACAS.portatilPendiente)
    await expect(page.getByRole('button', { name: 'Garantía' })).toBeVisible()

    await page.getByRole('button', { name: 'Garantía' }).click()
    await page.getByPlaceholder('Serial del disco SSD/HDD').fill('SD-E2E-001')
    await page.getByRole('button', { name: 'Fábrica' }).click()
    await page.getByPlaceholder('Número de ticket del proveedor').fill('TICKET-E2E')
    await page.getByRole('button', { name: 'Sala de Garantía' }).click()
    await page.getByRole('button', { name: 'Guardar diagnóstico' }).click()

    await expect(page.getByText('Pendiente garantía')).toBeVisible()

    // 2) Re-escaneo: registro único abierto → modal de cierre → Almacén General.
    await escanear(page, PLACAS.portatilPendiente)
    await expect(page.getByRole('dialog', { name: 'Cerrar registro de portátil' })).toBeVisible()

    await page.getByLabel('Reparado por').fill('Taller E2E')
    await page.getByRole('button', { name: 'Cerrar hacia Almacén' }).click()

    await expect(page.getByText('Intervenido')).toBeVisible()
  })
})
