import { equipoResumen } from './componentes'
import type { EquipoTraslado } from './types'

interface ListaEquiposProps {
  equipos: EquipoTraslado[]
  onQuitar?: (equipo: EquipoTraslado) => void
  quitarPendiente?: boolean
}

/** Listado de equipos de un traslado con estado de recepción y acción opcional. */
export function ListaEquipos({ equipos, onQuitar, quitarPendiente }: ListaEquiposProps) {
  if (equipos.length === 0) {
    return <p className="text-sm text-slate-400">Sin equipos asociados a este traslado.</p>
  }

  return (
    <ul className="divide-y divide-line">
      {equipos.map((equipo) => (
        <li key={equipo.id} className="flex items-center justify-between gap-3 py-2 text-sm">
          <div className="min-w-0">
            <p className="font-mono text-slate-900">{equipo.placa_id}</p>
            {equipo.serial && (
              <p className="font-mono text-xs text-slate-500">SN: {equipo.serial}</p>
            )}
            <p className="truncate text-xs text-slate-500">{equipoResumen(equipo)}</p>
          </div>

          <div className="flex shrink-0 items-center gap-2">
            {equipo.recibido && <span className="text-xs font-medium text-estado-verde-ink">✓ Recibido</span>}
            {onQuitar && !equipo.recibido && (
              <button
                type="button"
                onClick={() => onQuitar(equipo)}
                disabled={quitarPendiente}
                className="rounded-md border border-line px-2 py-1 text-xs font-medium text-slate-600 transition-colors hover:border-rose-200 hover:text-rose-700 disabled:cursor-not-allowed disabled:opacity-40"
              >
                Quitar
              </button>
            )}
          </div>
        </li>
      ))}
    </ul>
  )
}
