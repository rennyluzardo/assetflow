import { memo } from 'react'
import { flexRender } from '@tanstack/react-table'
import { cn } from '@/lib/cn'
import type { InventarioFila } from '../useInventarioTable'

const CLASES_CELDA: Record<string, string> = {
  descripcion: 'max-w-64 truncate',
  intervenido: 'text-center',
  acciones: 'text-right',
}

/** Fila memoizada: evita re-renderizar toda la tabla densa al cambiar un filtro. */
export const FilaInventario = memo(function FilaInventario({
  fila,
  atenuada,
}: {
  fila: InventarioFila
  atenuada: boolean
}) {
  return (
    <tr
      className={cn(
        'border-b border-line transition-colors last:border-0 hover:bg-muted',
        atenuada && 'opacity-60',
      )}
    >
      {fila.getAllCells().map((celda) => (
        <td
          key={celda.id}
          className={cn('whitespace-nowrap px-3 py-2', CLASES_CELDA[celda.column.id])}
        >
          {flexRender(celda.column.columnDef.cell, celda.getContext())}
        </td>
      ))}
    </tr>
  )
})
