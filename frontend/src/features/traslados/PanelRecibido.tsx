import { ListaEquipos } from './ListaEquipos'
import type { EquipoTraslado } from './types'

interface PanelRecibidoProps {
  equipos: EquipoTraslado[]
}

/** Panel del traslado RECIBIDO: solo lectura del lote recibido en su totalidad. */
export function PanelRecibido({ equipos }: PanelRecibidoProps) {
  const recibidos = equipos.filter((equipo) => equipo.recibido).length

  return (
    <div className="space-y-4">
      <div>
        <h3 className="mb-2 text-sm font-semibold text-slate-700">
          Equipos del traslado ({recibidos}/{equipos.length} recibidos)
        </h3>
        <ListaEquipos equipos={equipos} />
      </div>
    </div>
  )
}
