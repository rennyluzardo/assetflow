import type { Page } from '@playwright/test'
import { fileURLToPath } from 'node:url'

/** PNG válido de 1×1 para el adjunto de evidencia del módulo /soplado. */
export const EVIDENCIA_PNG = fileURLToPath(new URL('../fixtures/evidencia.png', import.meta.url))

/** Placas de prueba sembradas por `InventarioMockSeeder`. */
export const PLACAS = {
  /** Pendiente (sin intervención): punto de partida del flujo CPU. */
  cpuPendiente: 'AF-100001',
  /** Pendiente (sin intervención): punto de partida del flujo Portátil. */
  portatilPendiente: 'AF-100101',
  /** CPU ya cerrada vía soplado: hoja de vida completa para trazabilidad. */
  cpuConHistoria: 'AF-100016',
} as const

export const CREDENCIALES = {
  admin: { email: 'admin@assetflow.local', password: 'password' },
  cpu: { email: 'cpu@assetflow.local', password: 'password' },
} as const

/** Inicia sesión con el usuario indicado y espera aterrizar en /dashboard. */
export async function login(page: Page, email: string, password = 'password'): Promise<void> {
  await page.goto('/login')
  await page.getByLabel('Correo corporativo').fill(email)
  await page.getByLabel('Contraseña').fill(password)
  await page.getByRole('button', { name: 'Ingresar' }).click()
  await page.waitForURL('**/dashboard')
}

/** Escribe la placa en la pistola de escaneo y dispara la búsqueda con Enter. */
export async function escanear(page: Page, placa: string): Promise<void> {
  await page.getByTestId('scanner-input').fill(placa)
  await page.getByTestId('scanner-input').press('Enter')
}
