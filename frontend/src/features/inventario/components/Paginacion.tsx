import type { InventarioMeta } from '../types'

/** Controles de paginación con rango y conteo total (fuente: `meta` del backend). */
export function Paginacion({
  meta,
  onPagina,
}: {
  meta: InventarioMeta
  onPagina: (pagina: number) => void
}) {
  const { current_page: pagina, last_page: ultima, total, from, to } = meta

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-line px-3 py-2.5">
      <span className="text-xs text-slate-500">
        Mostrando{' '}
        <span className="font-mono">
          {from ?? 0}–{to ?? 0}
        </span>{' '}
        de <span className="font-mono">{total}</span>
      </span>
      <div className="flex items-center gap-2">
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
