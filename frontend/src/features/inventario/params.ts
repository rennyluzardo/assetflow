import type { InventarioParams, InventarioSearchParams } from './types'

/**
 * Traduce los search params a los parámetros del API.
 *
 * `direction` solo se envía si hay columna: un `sort` vacío con `direction`
 * residual produciría una query inválida en el backend.
 */
export function construirParams(search: InventarioSearchParams): InventarioParams {
  return {
    page: search.page,
    per_page: search.per_page,
    search: search.search || undefined,
    num_traslado: search.num_traslado || undefined,
    estado_actual: search.estado_actual || undefined,
    sort: search.sort || undefined,
    direction: search.sort ? search.direction || undefined : undefined,
  }
}
