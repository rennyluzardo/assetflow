import type { EventoTrazabilidad } from './types'

const CAMPOS_LEGADOS = ['foto', 'foto_url', 'foto_equipo', 'foto_ruta'] as const
const NOMBRE_DESCARGA = 'evidencia-placa.jpg'

/** Garantiza que una ruta relativa apunte al disco público (`/storage/...`). */
function aUrlPublica(ruta: string): string {
  const limpia = ruta.trim()
  if (limpia.startsWith('/') || /^[a-z][a-z\d+.-]*:/i.test(limpia)) return limpia
  return `/storage/${limpia}`
}

/**
 * Normaliza las evidencias de un hito: usa `fotos` cuando el backend ya las
 * resolvió y, si no, recompone el arreglo desde los campos individuales
 * heredados (`foto`, `foto_url`, `foto_equipo`, `foto_ruta`).
 */
export function normalizarFotos(evento: EventoTrazabilidad): string[] {
  const declaradas = (evento.fotos ?? []).filter((foto) => foto.trim() !== '')

  const rutas =
    declaradas.length > 0
      ? declaradas
      : CAMPOS_LEGADOS.flatMap((campo) => {
          const valor = evento[campo]
          return typeof valor === 'string' && valor.trim() !== '' ? [valor] : []
        })

  return Array.from(new Set(rutas.map(aUrlPublica)))
}

/** Descarga la evidencia como blob y delega el guardado al navegador. */
export async function descargarEvidencia(url: string): Promise<void> {
  try {
    const respuesta = await fetch(url)
    if (!respuesta.ok) throw new Error('No se pudo descargar la evidencia')

    const objectUrl = URL.createObjectURL(await respuesta.blob())
    dispararDescarga(objectUrl, NOMBRE_DESCARGA)
    setTimeout(() => URL.revokeObjectURL(objectUrl), 0)
  } catch {
    // Fallback: abrir la evidencia directamente y dejar que el navegador la guarde.
    dispararDescarga(url, NOMBRE_DESCARGA, true)
  }
}

function dispararDescarga(url: string, nombre: string, nuevaPestana = false): void {
  const enlace = document.createElement('a')
  enlace.href = url
  enlace.download = nombre
  if (nuevaPestana) {
    enlace.target = '_blank'
    enlace.rel = 'noopener'
  }
  document.body.appendChild(enlace)
  enlace.click()
  enlace.remove()
}
