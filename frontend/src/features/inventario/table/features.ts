import { rowPaginationFeature, rowSortingFeature, tableFeatures } from '@tanstack/react-table'

/**
 * Features mínimas de la tabla maestra.
 *
 * El orden y la paginación los resuelve el backend, así que NO se registran
 * `sortedRowModel` ni `paginatedRowModel`: la tabla solo renderiza el lote que
 * devuelve la API.
 */
export const inventarioFeatures = tableFeatures({
  rowSortingFeature,
  rowPaginationFeature,
})

export type InventarioTableFeatures = typeof inventarioFeatures
