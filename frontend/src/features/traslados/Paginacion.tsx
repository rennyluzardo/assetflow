import type { TrasladoMeta } from './types'

const TAMANIOS = [10, 25, 50] as const

interface PaginacionProps {
  meta: TrasladoMeta
  onPagina: (pagina: number) => void
  onTamanio: (perPage: number) => void
}

/** Controles de paginación con selector de tamaño de página (fuente: `meta`). */
export function Paginacion({ meta, onPagina, onTamanio }: PaginacionProps) {
  const { current_page: pagina, last_page: ultima, per_page: tamanio, total, from, to } = meta

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-line px-3 py-2.5">
      <span className="text-xs text-slate-500">
        Mostrando{' '}
        <span className="font-mono">
          {from ?? 0}–{to ?? 0}
        </span>{' '}
        de <span className="font-mono">{total}</span>
      </span>

      <div className="flex items-center gap-3">
        <select
          aria-label="Filas por página"
          value={tamanio}
          onChange={(event) => onTamanio(Number(event.target.value))}
          className="rounded-md border border-line bg-surface px-2 py-1 text-xs text-slate-600 outline-none focus:border-slate-400"
        >
          {TAMANIOS.map((opcion) => (
            <option key={opcion} value={opcion}>
              {opcion} / página
            </option>
          ))}
        </select>

        <button
          type="button"
          onClick={() => onPagina(pagina - 1)}
          disabled={pagina <= 1}
          className="rounded-md border border-line px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
        >
          Anterior
        </button>
        <span className="font-mono text-xs text-slate-600">
          Página {pagina} / {ultima}
        </span>
        <button
          type="button"
          onClick={() => onPagina(pagina + 1)}
          disabled={pagina >= ultima}
          className="rounded-md border border-line px-2.5 py-1 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
        >
          Siguiente
        </button>
      </div>
    </div>
  )
}
