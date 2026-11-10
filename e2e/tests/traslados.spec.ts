import { test, expect } from '@playwright/test'
import { execSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'
import { login, CREDENCIALES } from './fixtures'

const BACKEND_DIR = fileURLToPath(new URL('../../backend', import.meta.url))

/**
 * Al terminar, elimina las cabeceras `TR-E2E-*` creadas por esta suite para no
 * dejar basura en `/traslados` de la base de datos de desarrollo. Reutiliza el
 * mismo comando que ya limpia las placas reservadas (`e2e:reset-fixtures`).
 */
test.afterAll(() => {
  execSync('php artisan e2e:reset-fixtures', { cwd: BACKEND_DIR, stdio: 'inherit' })
})

test.describe('Traslados: maestro-detalle', () => {
  test('registra un traslado desde la modal y abre su detalle en el drawer', async ({ page }) => {
    await login(page, CREDENCIALES.admin.email)

    await page.getByRole('link', { name: 'Gestión y Recibos' }).click()
    await expect(page).toHaveURL(/\/traslados$/)

    // Vista maestro: buscador a la izquierda y CTA principal a la derecha.
    await expect(page.getByPlaceholder('Buscar por Código de traslado...')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Registrar traslado' })).toBeVisible()

    // Abrir la modal de registro (figura-fondo).
    await page.getByRole('button', { name: 'Registrar traslado' }).click()
    const dialog = page.getByRole('dialog', { name: 'Registrar traslado' })
    await expect(dialog).toBeVisible()

    // Llenar el formulario de creación.
    const codigo = `TR-E2E-${Date.now()}`
    await dialog.getByLabel('Código de traslado').fill(codigo)
    await dialog.getByLabel('Zona de origen').selectOption('BOGOTA')
    await dialog.getByLabel('Zona de destino').selectOption('MEDELLIN')
    await dialog.getByLabel('Ubicación exacta de origen').fill('Bodega Norte')
    await dialog.getByLabel('Ubicación exacta de destino').fill('Sala 2')

    // Guardar y verificar que la modal se cierra y el traslado aparece en la tabla.
    await dialog.getByRole('button', { name: 'Guardar' }).click()
    await expect(page.getByRole('dialog', { name: 'Registrar traslado' })).toHaveCount(0)
    await expect(page.getByRole('row', { name: new RegExp(codigo) })).toBeVisible()

    // Abrir el drawer lateral (preservación de contexto) y validar su contenido.
    await page.getByRole('row', { name: new RegExp(codigo) }).click()
    const drawer = page.getByRole('dialog', { name: new RegExp(codigo) })
    await expect(drawer).toBeVisible()
    await expect(drawer.getByRole('heading', { name: 'Lista de equipos del traslado' })).toBeVisible()
    await expect(drawer.getByText('Sin equipos asociados a este traslado.')).toBeVisible()

    // El administrador (sin sede) puede recibir cualquier traslado en tránsito.
    await expect(drawer.getByRole('button', { name: 'Recibir Traslado' })).toBeVisible()
  })
})
