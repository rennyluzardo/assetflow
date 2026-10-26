import { useMemo } from 'react'
import { useTable } from '@tanstack/react-table'
import type { OnChangeFn, SortingState } from '@tanstack/react-table'
import { COLUMNAS_INVENTARIO } from './columnas'
import { inventarioFeatures } from './table/features'
import type { InventarioItem, InventarioMeta } from './types'

interface Parametros {
  registros: InventarioItem[]
  meta: InventarioMeta | undefined
  sorting: SortingState
  onSortingChange: OnChangeFn<SortingState>
}

/**
 * Instancia controlada de TanStack Table.
 *
 * `manualSorting`/`manualPagination` desactivan el procesamiento local: la API
 * ya devuelve el lote ordenado y paginado.
 */
export function useInventarioTable({ registros, meta, sorting, onSortingChange }: Parametros) {
  const state = useMemo(() => ({ sorting }), [sorting])

  return useTable({
    features: inventarioFeatures,
    columns: COLUMNAS_INVENTARIO,
    data: registros,
    state,
    onSortingChange,
    manualSorting: true,
    manualPagination: true,
    rowCount: meta?.total ?? 0,
    enableMultiSort: false,
    getRowId: (fila) => String(fila.id),
  })
}

export type InventarioTabla = ReturnType<typeof useInventarioTable>
export type InventarioFila = ReturnType<InventarioTabla['getRowModel']>['rows'][number]
export type InventarioEncabezado =
  ReturnType<InventarioTabla['getHeaderGroups']>[number]['headers'][number]
