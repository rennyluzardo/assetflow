import { test, expect } from '@playwright/test'
import { login, escanear, PLACAS, CREDENCIALES, EVIDENCIA_PNG } from './fixtures'

test.describe('Cadena CPU + Soplado', () => {
  test('diagnóstico sucio → POR_SOPLAR → despacho → COMPLETADO', async ({ page }) => {
    await login(page, CREDENCIALES.admin.email)

    // 1) /equipos — novedad con polvo: queda en cola de soplado.
    await page.getByRole('link', { name: 'Equipos' }).click()
    await page.waitForURL('**/equipos')

    await escanear(page, PLACAS.cpuPendiente)
    await expect(page.getByRole('button', { name: 'Novedad' })).toBeVisible()

    await page.getByRole('button', { name: 'Novedad' }).click()
    await page.getByRole('button', { name: 'Sí', exact: true }).click()
    await page.getByRole('button', { name: 'Sala de Garantía' }).click()
    await page.getByRole('button', { name: 'Guardar registro' }).click()

    await expect(page.getByText('Por soplar')).toBeVisible()

    // 2) /soplado — limpia, adjunta evidencia y despacha a Almacén.
    await page.getByRole('link', { name: 'Soplado' }).click()
    await page.waitForURL('**/soplado')

    await escanear(page, PLACAS.cpuPendiente)
    await expect(page.getByRole('button', { name: 'Almacén' })).toBeVisible()

    await page.locator('input[type="file"]').setInputFiles(EVIDENCIA_PNG)
    await page.getByRole('button', { name: 'Almacén' }).click()
    await page.getByRole('button', { name: 'Registrar soplado y despachar' }).click()

    await expect(page.getByText('Completado')).toBeVisible()
  })
})
