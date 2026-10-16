import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import type { InventarioParams, InventarioResponse } from './types'

/** Tabla maestra paginada con búsqueda y filtros. Mantiene la página previa durante la carga. */
export function useInventario(params: InventarioParams) {
  return useQuery<InventarioResponse>({
    queryKey: ['inventario', params],
    queryFn: () => api.listarInventario(params),
    placeholderData: keepPreviousData,
  })
}
