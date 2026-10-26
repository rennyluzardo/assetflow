import { useMemo } from 'react'
import { useNavigate, useSearch } from '@tanstack/react-router'
import type { OnChangeFn, SortingState } from '@tanstack/react-table'
import { esColumnaOrdenable } from './orden'

/**
 * Orden como parte de la URL.
 *
 * El `SortingState` se deriva de los search params y la navegación ocurre SOLO
 * en el handler (nunca en un efecto), así que no hay doble estado ni ciclo
 * derivar → navegar → re-render.
 */
export function useInventarioOrden() {
  const search = useSearch({ from: '/inventario' })
  const navigate = useNavigate({ from: '/inventario' })

  const sorting = useMemo<SortingState>(() => {
    if (!search.sort) return []
    return [{ id: search.sort, desc: search.direction === 'desc' }]
  }, [search.sort, search.direction])

  const onSortingChange: OnChangeFn<SortingState> = (updater) => {
    const siguiente = typeof updater === 'function' ? updater(sorting) : updater
    const primera = siguiente[0]
    const columna = primera && esColumnaOrdenable(primera.id) ? primera.id : ''
    const direction = columna && primera ? (primera.desc ? 'desc' : 'asc') : ''

    navigate({
      search: (prev) => ({ ...prev, sort: columna, direction, page: 1 }),
      replace: true,
    })
  }

  return { sorting, onSortingChange }
}
