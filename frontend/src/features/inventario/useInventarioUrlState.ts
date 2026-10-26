import { useEffect, useMemo, useState } from 'react'
import { useNavigate, useSearch } from '@tanstack/react-router'
import { useDebouncedValue } from '@/lib/useDebouncedValue'
import { construirParams } from './params'
import { useInventarioOrden } from './useInventarioOrden'

/**
 * Fuente única de verdad de la tabla: filtros, paginación y orden viven en la
 * URL. No hay estado local duplicado que pueda desincronizarse.
 */
export function useInventarioUrlState() {
  const search = useSearch({ from: '/inventario' })
  const navigate = useNavigate({ from: '/inventario' })
  const { sorting, onSortingChange } = useInventarioOrden()

  // Debounce local solo para no reescribir la URL en cada tecla.
  const [filtroTexto, setFiltroTexto] = useState({
    search: search.search,
    num_traslado: search.num_traslado,
  })
  const filtroDebounced = useDebouncedValue(filtroTexto, 300)

  useEffect(() => {
    if (
      filtroDebounced.search !== search.search ||
      filtroDebounced.num_traslado !== search.num_traslado
    ) {
      navigate({
        search: (prev) => ({
          ...prev,
          search: filtroDebounced.search,
          num_traslado: filtroDebounced.num_traslado,
          page: 1,
        }),
        replace: true,
      })
    }
  }, [filtroDebounced, search.search, search.num_traslado, navigate])

  const params = useMemo(() => construirParams(search), [search])

  const irPagina = (pagina: number) =>
    navigate({ search: (prev) => ({ ...prev, page: pagina }), replace: true })

  const cambiarEstado = (estado: string) =>
    navigate({ search: (prev) => ({ ...prev, estado_actual: estado, page: 1 }), replace: true })

  const cambiarTamanio = (perPage: number) =>
    navigate({ search: (prev) => ({ ...prev, per_page: perPage, page: 1 }), replace: true })

  return {
    search,
    filtroTexto,
    setFiltroTexto,
    params,
    sorting,
    onSortingChange,
    irPagina,
    cambiarEstado,
    cambiarTamanio,
  }
}
