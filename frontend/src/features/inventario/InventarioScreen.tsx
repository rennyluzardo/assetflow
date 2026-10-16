import { memo, useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useSearch } from '@tanstack/react-router'
import { ApiError } from '@/lib/api'
import { cn } from '@/lib/cn'
import { StatusBadge } from '@/components/StatusBadge'
import { ESTADO_LABELS, ESTADOS, UBICACION_LABELS } from '@/constants/equipos'
import type { EstadoEquipo } from '@/constants/equipos'
import { useInventario } from './api'
import type { InventarioItem, InventarioMeta, InventarioParams } from './types'

const TAMANIOS_PAGINA = [250, 500, 1000] as const

/** Devuelve el valor pospuesto `delay` ms, útil para no saturar la URL al teclear. */
function useDebouncedValue<T>(value: T, delay: number): T {
  const [debounced, setDebounced] = useState(value)
  useEffect(() => {
    const id = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(id)
  }, [value, delay])
  return debounced
}

/** Tabla maestra de inventario con búsqueda, filtros y paginación sincronizados con la URL. */
export function InventarioScreen() {
  const search = useSearch({ from: '/inventario' })
  const navigate = useNavigate({ from: '/inventario' })

  // Filtros de texto con debounce local para no reescribir la URL en cada tecla.
  const [filtroTexto, setFiltroTexto] = useState({
    search: search.search,
    num_traslado: search.num_traslado,
  })
  const filtroDebounced = useDebouncedValue(filtroTexto, 300)

  useEffect(() => {
    if (
      filtroDebounced.search !== search.search ||
      filtroDebounced.num_traslado !== search.num_traslado
    ) {
      navigate({
        search: (prev) => ({
          ...prev,
          search: filtroDebounced.search,
          num_traslado: filtroDebounced.num_traslado,
          page: 1,
        }),
        replace: true,
      })
    }
  }, [filtroDebounced, search.search, search.num_traslado, navigate])

  const params: InventarioParams = useMemo(
    () => ({
      page: search.page,
      per_page: search.per_page,
      search: search.search || undefined,
      num_traslado: search.num_traslado || undefined,
      estado_actual: search.estado_actual || undefined,
    }),
    [search.page, search.per_page, search.search, search.num_traslado, search.estado_actual],
  )

  const { data, isLoading, isFetching, isError, error } = useInventario(params)
  const registros = data?.data ?? []
  const meta = data?.meta

  const irPagina = (pagina: number) =>
    navigate({ search: (prev) => ({ ...prev, page: pagina }), replace: true })

  const cambiarEstado = (estado: string) =>
    navigate({ search: (prev) => ({ ...prev, estado_actual: estado, page: 1 }), replace: true })

  const cambiarTamanio = (perPage: number) =>
    navigate({ search: (prev) => ({ ...prev, per_page: perPage, page: 1 }), replace: true })

  return (
    <div className="flex flex-col gap-4 p-6">
      <header className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Inventario General</h1>
          <p className="mt-1 text-sm text-slate-500">Tabla maestra de activos y estado de intervención.</p>
        </div>
        {meta && (
          <p className="text-sm text-slate-500">
            <span className="font-mono">{meta.total}</span> registros
          </p>
        )}
      </header>

      <div className="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface p-3">
        <input
          type="search"
          value={filtroTexto.search}
          onChange={(event) => setFiltroTexto((prev) => ({ ...prev, search: event.target.value }))}
          placeholder="Buscar por placa, serial o descripción…"
          aria-label="Búsqueda rápida"
          autoComplete="off"
          className="min-w-56 flex-1 rounded-md border border-line bg-canvas px-3 py-2 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-300"
        />
        <input
          type="text"
          value={filtroTexto.num_traslado}
          onChange={(event) => setFiltroTexto((prev) => ({ ...prev, num_traslado: event.target.value }))}
          placeholder="N° traslado"
          aria-label="Filtrar por número de traslado"
          autoComplete="off"
          spellCheck={false}
          className="w-44 rounded-md border border-line bg-canvas px-3 py-2 font-mono text-sm text-slate-900 outline-none transition-colors placeholder:font-sans placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-300"
        />
        <select
          value={search.estado_actual}
          onChange={(event) => cambiarEstado(event.target.value)}
          aria-label="Filtrar por estado"
          className="rounded-md border border-line bg-canvas px-3 py-2 text-sm text-slate-700 outline-none transition-colors focus:border-slate-400"
        >
          <option value="">Todos los estados</option>
          <option value="SIN_ESTADO">Sin estado</option>
          {ESTADOS.map((estado) => (
            <option key={estado} value={estado}>
              {ESTADO_LABELS[estado]}
            </option>
          ))}
        </select>
        <select
          value={search.per_page}
          onChange={(event) => cambiarTamanio(Number.parseInt(event.target.value, 10))}
          aria-label="Registros por página"
          className="rounded-md border border-line bg-canvas px-3 py-2 text-sm text-slate-700 outline-none transition-colors focus:border-slate-400"
        >
          {TAMANIOS_PAGINA.map((tamano) => (
            <option key={tamano} value={tamano}>
              {tamano} / página
            </option>
          ))}
        </select>
      </div>

      <div className="overflow-hidden rounded-lg border border-line bg-surface">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left">
            <thead>
              <tr className="border-b border-line bg-muted/60 text-xs font-medium uppercase tracking-wide text-slate-500">
                <th className="px-3 py-2.5">Placa</th>
                <th className="px-3 py-2.5">Serial</th>
                <th className="px-3 py-2.5">Descripción</th>
                <th className="px-3 py-2.5">Marca</th>
                <th className="px-3 py-2.5">Traslado</th>
                <th className="px-3 py-2.5">Ubicación</th>
                <th className="px-3 py-2.5">Estado</th>
                <th className="px-3 py-2.5 text-center">Cierre</th>
                <th className="px-3 py-2.5 text-right">Acción</th>
              </tr>
            </thead>
            <tbody className={cn(isFetching && !isLoading && 'opacity-60')}>
              {registros.map((item) => (
                <FilaInventario key={item.id} item={item} />
              ))}
            </tbody>
          </table>
        </div>

        {isLoading && (
          <p className="border-t border-line px-3 py-8 text-center text-sm text-slate-400">
            Cargando inventario…
          </p>
        )}

        {!isLoading && isError && (
          <p className="border-t border-line px-3 py-8 text-center text-sm text-estado-terracota-ink">
            {error instanceof ApiError ? error.message : 'No se pudo cargar el inventario.'}
          </p>
        )}

        {!isLoading && !isError && registros.length === 0 && (
          <p className="border-t border-line px-3 py-8 text-center text-sm text-slate-400">
            No se encontraron registros con los filtros actuales.
          </p>
        )}

        {meta && !isLoading && !isError && (
          <Paginacion meta={meta} onPagina={irPagina} />
        )}
      </div>
    </div>
  )
}

/** Fila de la tabla maestra. Memoizada para mantener la tabla densa sin re-render innecesario. */
const FilaInventario = memo(function FilaInventario({ item }: { item: InventarioItem }) {
  return (
    <tr className="border-b border-line transition-colors last:border-0 hover:bg-muted">
      <td className="whitespace-nowrap px-3 py-2 font-mono text-xs font-medium text-slate-900">
        {item.placa_id}
      </td>
      <td className="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-500">{item.serial}</td>
      <td className="max-w-64 truncate px-3 py-2 text-xs text-slate-700">{item.descripcion}</td>
      <td className="whitespace-nowrap px-3 py-2 text-xs text-slate-600">{item.marca ?? '—'}</td>
      <td className="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-600">{item.num_traslado}</td>
      <td className="whitespace-nowrap px-3 py-2 text-xs text-slate-600">
        {item.ubicacion_actual ? UBICACION_LABELS[item.ubicacion_actual] ?? item.ubicacion_actual : '—'}
      </td>
      <td className="whitespace-nowrap px-3 py-2">
        <CeldaEstado estado={item.estado_actual} />
      </td>
      <td className="whitespace-nowrap px-3 py-2 text-center text-xs text-slate-500">
        {item.intervenido ? <span className="text-estado-verde-ink">✓</span> : '—'}
      </td>
      <td className="whitespace-nowrap px-3 py-2 text-right">
        <Link
          to="/trazabilidad"
          search={{ placa_id: item.placa_id }}
          className="rounded-md border border-line px-2 py-1 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900"
        >
          Trazar
        </Link>
      </td>
    </tr>
  )
})

/** Estado semántico o píldora neutra cuando el activo aún no ha sido intervenido. */
function CeldaEstado({ estado }: { estado: EstadoEquipo | null }) {
  if (estado === null) {
    return (
      <span className="inline-flex items-center rounded-full border border-line bg-muted px-2.5 py-0.5 text-xs font-medium text-slate-500">
        Sin estado
      </span>
    )
  }
  return <StatusBadge estado={estado} />
}

/** Controles de paginación con rango y conteo total. */
function Paginacion({ meta, onPagina }: { meta: InventarioMeta; onPagina: (pagina: number) => void }) {
  const { current_page: pagina, last_page: ultima, total, from, to } = meta

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-line px-3 py-2.5">
      <span className="text-xs text-slate-500">
        Mostrando <span className="font-mono">{from ?? 0}–{to ?? 0}</span> de{' '}
        <span className="font-mono">{total}</span>
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
