import { flexRender } from '@tanstack/react-table'
import { cn } from '@/lib/cn'
import type { InventarioEncabezado } from '../useInventarioTable'

const INDICADOR = { asc: '↑', desc: '↓', none: '↕' } as const

const ALINEACION: Record<string, string> = {
  intervenido: 'text-center',
  acciones: 'text-right',
}

/** Encabezado con control de orden accesible (`aria-sort`) cuando la columna lo admite. */
export function EncabezadoOrdenable({ header }: { header: InventarioEncabezado }) {
  const puedeOrdenar = header.column.getCanSort()
  const orden = header.column.getIsSorted()
  const ariaSort = orden === 'asc' ? 'ascending' : orden === 'desc' ? 'descending' : 'none'
  const contenido = header.isPlaceholder
    ? null
    : flexRender(header.column.columnDef.header, header.getContext())

  return (
    <th scope="col" aria-sort={ariaSort} className={cn('px-3 py-2.5', ALINEACION[header.column.id])}>
      {puedeOrdenar ? (
        <button
          type="button"
          onClick={(event) => header.column.getToggleSortingHandler()?.(event)}
          className="inline-flex items-center gap-1 transition-colors hover:text-slate-900"
        >
          {contenido}
          <span aria-hidden="true" className={orden ? 'text-slate-900' : 'text-slate-300'}>
            {INDICADOR[orden || 'none']}
          </span>
        </button>
      ) : (
        contenido
      )}
    </th>
  )
}
