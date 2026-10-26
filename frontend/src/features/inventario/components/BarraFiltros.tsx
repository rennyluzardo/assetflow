import { ESTADO_LABELS, ESTADOS } from '@/constants/equipos'
import { SelectFiltro } from './SelectFiltro'

export const TAMANIOS_PAGINA = [250, 500, 1000] as const

interface Props {
  search: string
  numTraslado: string
  estado: string
  perPage: number
  onSearch: (valor: string) => void
  onNumTraslado: (valor: string) => void
  onEstado: (valor: string) => void
  onPerPage: (valor: number) => void
}

const CAMPO =
  'rounded-md border border-line bg-canvas px-3 py-2 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-300'

/** Filtros de la tabla maestra. Presentacional: recibe valores y callbacks. */
export function BarraFiltros({
  search,
  numTraslado,
  estado,
  perPage,
  onSearch,
  onNumTraslado,
  onEstado,
  onPerPage,
}: Props) {
  return (
    <div className="mx-auto flex w-full max-w-7xl flex-wrap items-center gap-3 rounded-lg border border-line bg-surface p-3">
      <input
        type="search"
        value={search}
        onChange={(event) => onSearch(event.target.value)}
        placeholder="Buscar por placa, serial o descripción…"
        aria-label="Búsqueda rápida"
        autoComplete="off"
        className={`min-w-56 flex-1 ${CAMPO}`}
      />
      <input
        type="text"
        value={numTraslado}
        onChange={(event) => onNumTraslado(event.target.value)}
        placeholder="N° traslado"
        aria-label="Filtrar por número de traslado"
        autoComplete="off"
        spellCheck={false}
        className={`w-44 font-mono placeholder:font-sans ${CAMPO}`}
      />
      <SelectFiltro valor={estado} etiqueta="Filtrar por estado" onChange={onEstado}>
        <option value="">Todos los estados</option>
        <option value="SIN_ESTADO">Sin estado</option>
        {ESTADOS.map((valor) => (
          <option key={valor} value={valor}>
            {ESTADO_LABELS[valor]}
          </option>
        ))}
      </SelectFiltro>
      <SelectFiltro
        valor={perPage}
        etiqueta="Registros por página"
        onChange={(valor) => onPerPage(Number.parseInt(valor, 10))}
      >
        {TAMANIOS_PAGINA.map((tamano) => (
          <option key={tamano} value={tamano}>
            {tamano} / página
          </option>
        ))}
      </SelectFiltro>
    </div>
  )
}
