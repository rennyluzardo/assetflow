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

    // Evidencia fotográfica del soplado: miniatura ampliable en la línea de tiempo.
    const miniatura = timeline.getByRole('button', { name: /Ampliar evidencia fotográfica/ }).first()
    await expect(miniatura).toBeVisible()

    // El visor maximizado expone cierre y descarga; Escape lo cierra.
    await miniatura.click()
    const visor = page.getByRole('dialog', { name: 'Visor de evidencia fotográfica' })
    await expect(visor).toBeVisible()
    await expect(visor.getByRole('button', { name: 'Descargar' })).toBeVisible()
    await page.keyboard.press('Escape')
    await expect(visor).toBeHidden()
  })
})
