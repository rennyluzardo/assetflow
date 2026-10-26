import { createFileRoute } from '@tanstack/react-router'
import { InventarioScreen } from '@/features/inventario/InventarioScreen'
import { normalizarColumna, normalizarDireccion } from '@/features/inventario/orden'
import type { InventarioSearchParams } from '@/features/inventario/types'

/** Convierte un valor de query (string o número) en texto limpio. */
function texto(valor: unknown): string {
  return typeof valor === 'string' ? valor : ''
}

/** Convierte un valor de query en un entero positivo; cae a `porDefecto`. */
function entero(valor: unknown, porDefecto: number): number {
  if (typeof valor === 'string') {
    const parseado = Number.parseInt(valor, 10)
    if (Number.isInteger(parseado) && parseado > 0) return parseado
  }
  if (typeof valor === 'number' && Number.isInteger(valor) && valor > 0) return valor
  return porDefecto
}

export const Route = createFileRoute('/inventario')({
  validateSearch: (search: Record<string, unknown>): InventarioSearchParams => {
    // Sanea la URL: un `sort` manipulado fuera de la whitelist se descarta
    // antes de llegar al backend (que respondería 422).
    const sort = normalizarColumna(search.sort)

    return {
      search: texto(search.search),
      num_traslado: texto(search.num_traslado),
      estado_actual: texto(search.estado_actual),
      per_page: entero(search.per_page, 250),
      page: entero(search.page, 1),
      sort,
      direction: sort ? normalizarDireccion(search.direction) : '',
    }
  },
  component: InventarioScreen,
})
