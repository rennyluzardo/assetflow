import type { InventarioMeta } from '../types'
import type { InventarioTabla as Tabla } from '../useInventarioTable'
import { EncabezadoOrdenable } from './EncabezadoOrdenable'
import { EstadosTabla } from './EstadosTabla'
import { FilaInventario } from './FilaInventario'
import { Paginacion } from './Paginacion'

interface Props {
  table: Tabla
  meta: InventarioMeta | undefined
  cargando: boolean
  refrescando: boolean
  isError: boolean
  error: unknown
  onPagina: (pagina: number) => void
}

/** Tarjeta de la tabla maestra: cabecera ordenable, filas y estados. */
export function InventarioTabla({
  table,
  meta,
  cargando,
  refrescando,
  isError,
  error,
  onPagina,
}: Props) {
  const filas = table.getRowModel().rows
  const vacio = !cargando && !isError && filas.length === 0

  return (
    <div className="mx-auto w-full max-w-7xl overflow-hidden rounded-lg border border-line bg-surface">
      <div className="overflow-x-auto">
        <table className="w-full border-collapse text-left">
          <thead>
            {table.getHeaderGroups().map((grupo) => (
              <tr
                key={grupo.id}
                className="border-b border-line bg-muted/60 text-xs font-medium uppercase tracking-wide text-slate-500"
              >
                {grupo.headers.map((header) => (
                  <EncabezadoOrdenable key={header.id} header={header} />
                ))}
              </tr>
            ))}
          </thead>
          <tbody>
            {filas.map((fila) => (
              <FilaInventario key={fila.id} fila={fila} atenuada={refrescando && !cargando} />
            ))}
          </tbody>
        </table>
      </div>

      <EstadosTabla cargando={cargando} isError={isError} error={error} vacio={vacio} />

      {meta && !cargando && !isError && <Paginacion meta={meta} onPagina={onPagina} />}
    </div>
  )
}
