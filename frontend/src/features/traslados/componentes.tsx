import { cn } from '@/lib/cn'
import { ApiError } from '@/lib/api'
import { ESTADO_TRASLADO_CLASSES, ESTADO_TRASLADO_LABELS } from './constantes'
import type { EquipoTraslado, Traslado, TrasladoEstado } from './types'

export function EstadoTrasladoBadge({ estado }: { estado: TrasladoEstado }) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
        ESTADO_TRASLADO_CLASSES[estado],
      )}
    >
      {ESTADO_TRASLADO_LABELS[estado]}
    </span>
  )
}

/** Fecha legible en español; cae a guion cuando no hay valor. */
export function formatearFecha(iso: string | null | undefined): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('es-CO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

/** Extrae el mensaje más específico de un `ApiError` (por campo, si existe). */
export function mensajeError(error: ApiError | null): string | null {
  if (!error) return null
  const porCampo = Object.values(error.errors).flat()[0]
  return porCampo ?? error.message
}

/** Ruta legible de un traslado: origen → destino (el origen nace pendiente). */
export function rutaTraslado(traslado: Traslado): string {
  const origen = traslado.zona_origen ?? 'Origen pendiente'
  const destino = traslado.zona_destino
  return `${origen} → ${destino}`
}

/** Etiqueta de un equipo con su detalle de inventario cuando existe. */
export function equipoResumen(equipo: EquipoTraslado): string {
  return [equipo.marca, equipo.referencia].filter(Boolean).join(' ') || equipo.descripcion || '—'
}
