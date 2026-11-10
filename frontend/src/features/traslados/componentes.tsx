import { cn } from '@/lib/cn'
import { ApiError } from '@/lib/api'
import type { EquipoRecibido, Traslado, TrasladoEstado } from './types'

export function EstadoTrasladoBadge({ estado }: { estado: TrasladoEstado }) {
  const enTransito = estado === 'EN_TRANSITO'
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
        enTransito
          ? 'border-amber-200 bg-amber-50 text-amber-700'
          : 'border-emerald-200 bg-emerald-50 text-emerald-700',
      )}
    >
      {enTransito ? 'En tránsito' : 'Recibido'}
    </span>
  )
}

/** Listado compacto de equipos de un traslado (por `num_traslado`). */
export function EquiposLista({ equipos }: { equipos: EquipoRecibido[] }) {
  if (equipos.length === 0) {
    return <p className="text-sm text-slate-400">Sin equipos asociados a este traslado.</p>
  }

  return (
    <ul className="divide-y divide-line">
      {equipos.map((equipo) => (
        <li key={equipo.id} className="flex items-center justify-between py-2 text-sm">
          <span className="font-mono text-slate-900">{equipo.placa_id}</span>
          <span className="text-slate-500">
            {[equipo.marca, equipo.referencia].filter(Boolean).join(' ') || equipo.descripcion}
          </span>
        </li>
      ))}
    </ul>
  )
}

/** Extrae el mensaje más específico de un `ApiError` (por campo, si existe). */
export function mensajeError(error: ApiError | null): string | null {
  if (!error) return null
  const porCampo = Object.values(error.errors).flat()[0]
  return porCampo ?? error.message
}

/**
 * El botón "Recibir Traslado" solo se habilita en tránsito y cuando el operario
 * está en la sede de destino (sin sede asignada => administración sin restricción,
 * igual que valida el backend en `TrasladoService::recibir`).
 */
export function puedeRecibir(traslado: Traslado, sede: string | null | undefined): boolean {
  if (traslado.estado !== 'EN_TRANSITO') return false
  if (sede === null || sede === undefined || sede === '') return true
  return sede === traslado.zona_destino
}
