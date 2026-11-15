import type { TrasladoListaParams, TrasladoSearchParams } from './types'

/** Traduce los search params a los parámetros del API de listado. */
export function construirParams(search: TrasladoSearchParams): TrasladoListaParams {
  return {
    page: search.page,
    per_page: search.per_page,
    codigo: search.codigo || undefined,
    estado: search.estado || undefined,
  }
}
