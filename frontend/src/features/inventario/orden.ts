/**
 * Contrato de orden de la tabla maestra.
 *
 * Espejo exacto de `App\Enums\InventarioOrdenableEnum`. Se mantiene libre de
 * dependencias de React y de `@tanstack/react-table` para poder sanear la URL
 * desde `validateSearch` sin arrastrar la librería a la capa de rutas.
 */

export const COLUMNAS_ORDENABLES = [
  'placa_id',
  'serial',
  'descripcion',
  'marca',
  'num_traslado',
  'ubicacion_actual',
  'estado_actual',
  'intervenido',
] as const

export type ColumnaOrdenable = (typeof COLUMNAS_ORDENABLES)[number]

export type DireccionOrden = 'asc' | 'desc'

export function esColumnaOrdenable(valor: unknown): valor is ColumnaOrdenable {
  return typeof valor === 'string' && (COLUMNAS_ORDENABLES as readonly string[]).includes(valor)
}

/** Devuelve la columna si pertenece a la whitelist; si no, cadena vacía. */
export function normalizarColumna(valor: unknown): ColumnaOrdenable | '' {
  return esColumnaOrdenable(valor) ? valor : ''
}

/** Normaliza la dirección: `desc` explícito y `asc` por defecto. */
export function normalizarDireccion(valor: unknown): DireccionOrden {
  return valor === 'desc' ? 'desc' : 'asc'
}
