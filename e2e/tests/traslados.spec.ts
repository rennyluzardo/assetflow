import { test, expect } from '@playwright/test'
import { execSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'
import { login, CREDENCIALES } from './fixtures'

const BACKEND_DIR = fileURLToPath(new URL('../../backend', import.meta.url))

/** Código autogenerado del traslado creado por la suite, para limpiarlo al final. */
let codigoCreado: string | null = null

/**
 * Al terminar, elimina la cabecera creada por esta suite para no dejar basura
 * en `/traslados` de la base de datos de desarrollo. El código ahora es
 * autogenerado, así que se pasa al comando `e2e:reset-fixtures` vía `--codigo`.
 */
test.afterAll(() => {
  const codigo = codigoCreado ? ` --codigo=${codigoCreado}` : ''
  execSync(`php artisan e2e:reset-fixtures${codigo}`, { cwd: BACKEND_DIR, stdio: 'inherit' })
})

test.describe('Traslados: maestro-detalle', () => {
  test('registra un traslado desde la modal y abre su detalle en el drawer', async ({ page }) => {
    await login(page, CREDENCIALES.admin.email)

    await page.getByRole('link', { name: 'Gestión y Recibos' }).click()
    await expect(page).toHaveURL(/\/traslados$/)

    // Vista maestro: buscador a la izquierda y CTA principal a la derecha.
    await expect(page.getByLabel('Buscar por código de traslado')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Registrar traslado' })).toBeVisible()

    // Abrir la modal de apertura (figura-fondo).
    await page.getByRole('button', { name: 'Registrar traslado' }).click()
    const dialog = page.getByRole('dialog', { name: 'Registrar traslado' })
    await expect(dialog).toBeVisible()

    // Solo se elige el destino: el código es autogenerado y el origen nace pendiente.
    await dialog.getByLabel('Zona de destino').selectOption('BOGOTA')
    await expect(dialog.getByLabel('Ubicación de destino')).toBeEnabled()
    await dialog.getByLabel('Ubicación de destino').selectOption('SALA_DE_GARANTIA')

    // Abrir y verificar que la modal se cierra y el traslado aparece en la tabla.
    await dialog.getByRole('button', { name: 'Abrir traslado' }).click()
    await expect(page.getByRole('dialog', { name: 'Registrar traslado' })).toHaveCount(0)

    // El listado ordena por id descendente: la fila más reciente es la recién creada.
    const fila = page.locator('tbody tr').first()
    await expect(fila).toContainText('Origen pendiente → BOGOTA')
    await expect(fila).toContainText('Abierto')

    const codigo = (await fila.locator('td').first().textContent())?.trim() ?? ''
    expect(codigo).toMatch(/^\d+$/)
    codigoCreado = codigo

    // Abrir el drawer lateral (preservación de contexto) y validar su contenido.
    await fila.click()
    const drawer = page.getByRole('dialog', { name: 'Detalle de traslado' })
    await expect(drawer).toBeVisible()
    await expect(drawer.getByText(codigo)).toBeVisible()
    await expect(drawer.getByText('Origen pendiente → BOGOTA')).toBeVisible()
    await expect(drawer.getByText('Sin equipos asociados a este traslado.')).toBeVisible()

    // Estado ABIERTO sin equipos: el despacho queda deshabilitado hasta agregar lotes.
    await expect(drawer.getByRole('button', { name: 'Despachar traslado' })).toBeDisabled()
  })
})
