import { EstadoTrasladoBadge } from './componentes'
import { ZONA_LABELS } from './constantes'
import type { Traslado } from './types'

interface TrasladoTablaProps {
  traslados: Traslado[]
  onSeleccionar: (traslado: Traslado) => void
}

const TH =
  'sticky top-0 z-10 bg-muted px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-slate-500'

function fecha(iso: string): string {
  return new Date(iso).toLocaleDateString('es-CO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

/** Listado maestro unificado de traslados (enviados y recibidos). */
export function TrasladoTabla({ traslados, onSeleccionar }: TrasladoTablaProps) {
  if (traslados.length === 0) {
    return (
      <div className="flex flex-1 items-center justify-center p-8 text-sm text-slate-400">
        No se encontraron traslados.
      </div>
    )
  }

  return (
    <div className="min-h-0 flex-1 overflow-auto">
      <table className="w-full border-collapse text-sm">
        <thead>
          <tr>
            <th className={TH}>Código de traslado</th>
            <th className={TH}>Ruta</th>
            <th className={TH}>Estado</th>
            <th className={TH}>Equipos</th>
            <th className={TH}>Creado</th>
          </tr>
        </thead>
        <tbody>
          {traslados.map((traslado) => (
            <tr
              key={traslado.id}
              onClick={() => onSeleccionar(traslado)}
              onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                  event.preventDefault()
                  onSeleccionar(traslado)
                }
              }}
              tabIndex={0}
              className="cursor-pointer border-t border-line transition-colors hover:bg-muted focus:bg-muted focus:outline-none"
            >
              <td className="px-4 py-3 font-mono text-sm font-medium text-slate-900">{traslado.codigo}</td>
              <td className="px-4 py-3 text-sm text-slate-600">
                {ZONA_LABELS[traslado.zona_origen]} → {ZONA_LABELS[traslado.zona_destino]}
              </td>
              <td className="px-4 py-3">
                <EstadoTrasladoBadge estado={traslado.estado} />
              </td>
              <td className="px-4 py-3 font-mono text-sm text-slate-600">{traslado.equipos_count}</td>
              <td className="px-4 py-3 font-mono text-xs text-slate-400">{fecha(traslado.created_at)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
