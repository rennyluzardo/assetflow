import { useInventario } from './api'
import { BarraFiltros } from './components/BarraFiltros'
import { InventarioTabla } from './components/InventarioTabla'
import { useInventarioTable } from './useInventarioTable'
import { useInventarioUrlState } from './useInventarioUrlState'
import type { InventarioItem } from './types'

/** Lista vacía estable: un `[]` nuevo en cada render invalidaría los row models. */
const REGISTROS_VACIOS: InventarioItem[] = []

/** Orquestador de la tabla maestra. Todo el estado vive en la URL. */
export function InventarioScreen() {
  const url = useInventarioUrlState()
  const { data, isLoading, isFetching, isError, error } = useInventario(url.params)

  const table = useInventarioTable({
    registros: data?.data ?? REGISTROS_VACIOS,
    meta: data?.meta,
    sorting: url.sorting,
    onSortingChange: url.onSortingChange,
  })

  return (
    <div className="flex flex-col gap-4 p-6">
      <header className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
            Inventario General
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            Tabla maestra de activos y estado de intervención.
          </p>
        </div>
        {data?.meta && (
          <p className="text-sm text-slate-500">
            <span className="font-mono">{data.meta.total}</span> registros
          </p>
        )}
      </header>

      <BarraFiltros
        search={url.filtroTexto.search}
        numTraslado={url.filtroTexto.num_traslado}
        estado={url.search.estado_actual}
        perPage={url.search.per_page}
        onSearch={(valor) => url.setFiltroTexto((prev) => ({ ...prev, search: valor }))}
        onNumTraslado={(valor) =>
          url.setFiltroTexto((prev) => ({ ...prev, num_traslado: valor }))
        }
        onEstado={url.cambiarEstado}
        onPerPage={url.cambiarTamanio}
      />

      <InventarioTabla
        table={table}
        meta={data?.meta}
        cargando={isLoading}
        refrescando={isFetching}
        isError={isError}
        error={error}
        onPagina={url.irPagina}
      />
    </div>
  )
}
