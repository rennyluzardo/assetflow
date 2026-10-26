import { ApiError } from '@/lib/api'

interface Props {
  cargando: boolean
  isError: boolean
  error: unknown
  vacio: boolean
}

/** Carga, error y vacío de la tabla. Devuelve `null` cuando hay filas. */
export function EstadosTabla({ cargando, isError, error, vacio }: Props) {
  if (cargando) {
    return (
      <p className="border-t border-line px-3 py-8 text-center text-sm text-slate-400">
        Cargando inventario…
      </p>
    )
  }

  if (isError) {
    return (
      <p className="border-t border-line px-3 py-8 text-center text-sm text-estado-terracota-ink">
        {error instanceof ApiError ? error.message : 'No se pudo cargar el inventario.'}
      </p>
    )
  }

  if (vacio) {
    return (
      <p className="border-t border-line px-3 py-8 text-center text-sm text-slate-400">
        No se encontraron registros con los filtros actuales.
      </p>
    )
  }

  return null
}
