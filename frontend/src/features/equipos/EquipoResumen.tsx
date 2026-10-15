import type { InventarioBusqueda } from '@/types/equipos'
import { UBICACION_LABELS } from '@/constants/equipos'
import { StatusBadge } from '@/components/StatusBadge'
import { cn } from '@/lib/cn'

/** Datos autocompletados desde inventario al escanear la placa (sin digitación manual). */
export function EquipoResumen({ equipo }: { equipo: InventarioBusqueda }) {
  return (
    <dl className="max-w-2xl grid grid-cols-2 gap-x-6 gap-y-4 rounded-lg border border-line bg-surface p-5 sm:grid-cols-3">
      <Campo etiqueta="Placa" mono>
        {equipo.placa_id}
      </Campo>
      <Campo etiqueta="Serial" mono>
        {equipo.serial}
      </Campo>
      <Campo etiqueta="N.º Traslado" mono>
        {equipo.num_traslado}
      </Campo>
      <Campo etiqueta="Ubicación actual">
        {equipo.ubicacion_actual ? UBICACION_LABELS[equipo.ubicacion_actual] : '—'}
      </Campo>
      <Campo etiqueta="Referencia">{equipo.referencia ?? '—'}</Campo>
      <Campo etiqueta="Descripción">{equipo.descripcion}</Campo>
      {equipo.estado_actual && (
        <div>
          <dt className="text-xs text-slate-500">Estado actual</dt>
          <dd className="mt-1">
            <StatusBadge estado={equipo.estado_actual} />
          </dd>
        </div>
      )}
    </dl>
  )
}

function Campo({ etiqueta, mono, children }: { etiqueta: string; mono?: boolean; children: string }) {
  return (
    <div>
      <dt className="text-xs text-slate-500">{etiqueta}</dt>
      <dd className={cn('mt-0.5 break-all text-sm text-slate-900', mono && 'font-mono')}>{children}</dd>
    </div>
  )
}
