import { CAMPO, ESTADO_TRASLADO_LABELS, ESTADOS_TRASLADO } from './constantes'
import type { TrasladoEstado } from './types'

interface BarraFiltrosProps {
  codigo: string
  estado: TrasladoEstado | ''
  onCodigo: (codigo: string) => void
  onEstado: (estado: TrasladoEstado | '') => void
}

/** Barra de filtros del listado de traslados (código y estado). */
export function BarraFiltros({ codigo, estado, onCodigo, onEstado }: BarraFiltrosProps) {
  return (
    <div className="flex shrink-0 flex-wrap items-center gap-3">
      <input
        type="search"
        value={codigo}
        onChange={(event) => onCodigo(event.target.value)}
        placeholder="Buscar por código…"
        aria-label="Buscar por código de traslado"
        autoComplete="off"
        spellCheck={false}
        className={`${CAMPO} w-56 font-mono placeholder:font-sans`}
      />

      <select
        aria-label="Filtrar por estado"
        value={estado}
        onChange={(event) => onEstado(event.target.value as TrasladoEstado | '')}
        className={`${CAMPO} w-44`}
      >
        <option value="">Todos los estados</option>
        {ESTADOS_TRASLADO.map((opcion) => (
          <option key={opcion} value={opcion}>
            {ESTADO_TRASLADO_LABELS[opcion]}
          </option>
        ))}
      </select>
    </div>
  )
}
