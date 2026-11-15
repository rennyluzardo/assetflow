import { useEffect, useMemo, useState } from 'react'
import { useNavigate, useSearch } from '@tanstack/react-router'
import { useDebouncedValue } from '@/lib/useDebouncedValue'
import { construirParams } from './params'
import type { TrasladoEstado } from './types'

/**
 * Fuente única de verdad del listado de traslados: filtro por código y estado
 * viven en la URL. No hay estado local duplicado que pueda desincronizarse.
 */
export function useTrasladosUrlState() {
  const search = useSearch({ from: '/traslados' })
  const navigate = useNavigate({ from: '/traslados' })

  const [filtroCodigo, setFiltroCodigo] = useState(search.codigo)
  const codigoDebounced = useDebouncedValue(filtroCodigo, 300)

  useEffect(() => {
    if (codigoDebounced !== search.codigo) {
      navigate({
        search: (prev) => ({ ...prev, codigo: codigoDebounced, page: 1 }),
        replace: true,
      })
    }
  }, [codigoDebounced, search.codigo, navigate])

  const params = useMemo(() => construirParams(search), [search])

  const cambiarEstado = (estado: TrasladoEstado | '') =>
    navigate({ search: (prev) => ({ ...prev, estado, page: 1 }), replace: true })

  const irPagina = (pagina: number) =>
    navigate({ search: (prev) => ({ ...prev, page: pagina }), replace: true })

  const cambiarTamanio = (perPage: number) =>
    navigate({ search: (prev) => ({ ...prev, per_page: perPage, page: 1 }), replace: true })

  return { search, filtroCodigo, setFiltroCodigo, params, cambiarEstado, irPagina, cambiarTamanio }
}
