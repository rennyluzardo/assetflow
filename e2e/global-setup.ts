import { execSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'

/**
 * Prepara los fixtures de la suite antes de cada corrida: restablece las placas
 * reservadas (CPUs AF-100001..AF-100015 y portátiles AF-100101..AF-100110), la
 * hoja de vida AF-100016 y el maestro base de zonas/ubicaciones que alimenta el
 * selector de destino de `/traslados`. Todo vive en `e2e:reset-fixtures`, de modo
 * que `npx playwright test` sea idempotente sin depender de `migrate:fresh --seed`.
 */
export default function globalSetup(): void {
  const backendDir = fileURLToPath(new URL('../backend', import.meta.url))

  try {
    execSync('php artisan e2e:reset-fixtures', { cwd: backendDir, stdio: 'inherit' })
  } catch (error) {
    const detail = error instanceof Error ? error.message : String(error)
    throw new Error(
      'No se pudo restablecer los fixtures E2E (php artisan e2e:reset-fixtures). ' +
        '¿Está el backend levantado y PostgreSQL accesible?\n' +
        detail,
    )
  }
}
