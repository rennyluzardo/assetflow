import type { EstadoEquipo, UbicacionDestino } from '@/constants/equipos'

/** Registro de la tabla maestra devuelto por `GET /api/inventario`. */
export interface InventarioItem {
  id: number
  placa_id: string
  serial: string
  marca: string | null
  referencia: string | null
  descripcion: string
  num_traslado: string
  ubicacion_actual: UbicacionDestino | null
  estado_actual: EstadoEquipo | null
  intervenido: boolean
}

export interface InventarioMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

/** Envoltorio de `GET /api/inventario`. */
export interface InventarioResponse {
  data: InventarioItem[]
  meta: InventarioMeta
}

/** Parámetros de consulta soportados por la tabla maestra. */
export interface InventarioParams {
  page?: number
  per_page?: number
  search?: string
  num_traslado?: string
  estado_actual?: string
}

/** Filtros editables desde la URL de `/inventario`. */
export interface InventarioSearchParams {
  search: string
  num_traslado: string
  estado_actual: string
  per_page: number
  page: number
}
