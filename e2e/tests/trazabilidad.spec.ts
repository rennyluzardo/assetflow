import { test, expect } from '@playwright/test'
import { login, escanear, PLACAS, CREDENCIALES } from './fixtures'

test.describe('Trazabilidad: hoja de vida del activo', () => {
  test('renderiza ficha maestra y línea de tiempo cronológica CPU → Soplado', async ({ page }) => {
    await login(page, CREDENCIALES.admin.email)
    await page.getByRole('link', { name: 'Trazabilidad' }).click()
    await page.waitForURL('**/trazabilidad')

    await escanear(page, PLACAS.cpuConHistoria)

    // Ficha maestra: la placa consultada.
    await expect(page.getByText('AF-100016', { exact: true }).first()).toBeVisible()

    // Línea de tiempo: ingreso → diagnóstico CPU → soplado.
    const timeline = page.locator('ol')
    await expect(timeline.getByText('Inventario', { exact: true })).toBeVisible()
    await expect(timeline.getByText('CPU', { exact: true })).toBeVisible()
    await expect(timeline.getByText('Soplado', { exact: true })).toBeVisible()

    // Analista y ubicación asignada del despacho.
    await expect(timeline.getByText('Técnico CPU', { exact: true }).first()).toBeVisible()
    await expect(timeline.getByText('Almacén', { exact: true }).first()).toBeVisible()

    // Evidencia fotográfica del soplado (nombre de archivo en la línea de tiempo).
    await expect(page.getByText('fotos/soplado/AF-100016-interior.jpg').first()).toBeVisible()
  })
})
