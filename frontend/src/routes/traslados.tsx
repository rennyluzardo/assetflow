import { createFileRoute } from '@tanstack/react-router'
import { TrasladosScreen } from '@/features/traslados/TrasladosScreen'
import { ESTADOS_TRASLADO } from '@/features/traslados/constantes'
import type { TrasladoEstado, TrasladoSearchParams } from '@/features/traslados/types'

/** Convierte un valor de query en un entero positivo; cae a `porDefecto`. */
function entero(valor: unknown, porDefecto: number): number {
  if (typeof valor === 'string') {
    const parseado = Number.parseInt(valor, 10)
    if (Number.isInteger(parseado) && parseado > 0) return parseado
  }
  if (typeof valor === 'number' && Number.isInteger(valor) && valor > 0) return valor
  return porDefecto
}

/** Convierte un valor de query en texto limpio. */
function texto(valor: unknown): string {
  return typeof valor === 'string' ? valor : ''
}

/** Sanea el estado desde la URL: un valor fuera de la whitelist se descarta. */
function normalizarEstado(valor: unknown): TrasladoEstado | '' {
  const textoValor = texto(valor)
  return (ESTADOS_TRASLADO as readonly string[]).includes(textoValor)
    ? (textoValor as TrasladoEstado)
    : ''
}

export const Route = createFileRoute('/traslados')({
  validateSearch: (search: Record<string, unknown>): TrasladoSearchParams => ({
    codigo: texto(search.codigo),
    estado: normalizarEstado(search.estado),
    per_page: entero(search.per_page, 10),
    page: entero(search.page, 1),
  }),
  component: TrasladosScreen,
})
