import type { InventarioBusqueda } from '@/types/equipos'
import { UBICACION_LABELS } from '@/constants/equipos'

/** Datos autocompletados desde inventario al escanear una placa en cola de soplado. */
export function SopladoResumen({ equipo }: { equipo: InventarioBusqueda }) {
  return (
    <dl className="max-w-2xl grid grid-cols-1 gap-x-6 gap-y-4 rounded-lg border border-line bg-surface p-5 sm:grid-cols-3">
      <Campo etiqueta="Serial">{equipo.serial}</Campo>
      <Campo etiqueta="N.º Traslado">{equipo.num_traslado}</Campo>
      <Campo etiqueta="Ubicación actual">
        {equipo.ubicacion_actual ? UBICACION_LABELS[equipo.ubicacion_actual] : '—'}
      </Campo>
    </dl>
  )
}

function Campo({ etiqueta, children }: { etiqueta: string; children: string }) {
  return (
    <div>
      <dt className="text-xs text-slate-500">{etiqueta}</dt>
      <dd className="mt-0.5 break-all font-mono text-sm text-slate-900">{children}</dd>
    </div>
  )
}
