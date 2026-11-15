import type { TrasladoEstado } from './types'

export const ESTADO_TRASLADO_LABELS: Record<TrasladoEstado, string> = {
  ABIERTO: 'Abierto',
  EN_TRANSITO: 'En tránsito',
  RECIBIDO: 'Recibido',
}

export const ESTADOS_TRASLADO: readonly TrasladoEstado[] = ['ABIERTO', 'EN_TRANSITO', 'RECIBIDO']

/**
 * Semántica visual nórdica: `ABIERTO` y `EN_TRANSITO` son estados abiertos
 * (ámbar) y `RECIBIDO` es el cierre (verde).
 */
export const ESTADO_TRASLADO_CLASSES: Record<TrasladoEstado, string> = {
  ABIERTO: 'bg-estado-ambar text-estado-ambar-ink border-estado-ambar-line',
  EN_TRANSITO: 'bg-estado-ambar text-estado-ambar-ink border-estado-ambar-line',
  RECIBIDO: 'bg-estado-verde text-estado-verde-ink border-estado-verde-line',
}

/** Clase base compartida por inputs, selects y textareas del formulario nórdico. */
export const CAMPO =
  'w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-300'
