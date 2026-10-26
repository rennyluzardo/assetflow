import { useQuery } from '@tanstack/react-query'
import { api, getToken } from '@/lib/api'
import type { DashboardParams, DashboardResponse } from './types'

/** Métricas de control operativo (refetch al cambiar periodo/estación). */
export function useDashboard(params: DashboardParams) {
  return useQuery<DashboardResponse>({
    queryKey: ['dashboard', params],
    queryFn: () => api.dashboard(params),
  })
}

/**
 * Descarga la bitácora CSV con el header `Authorization: Bearer` y la guarda
 * como archivo. La API de streaming no es JSON, así que se usa `fetch` directo.
 */
export async function descargarBitacoraCsv(params: DashboardParams): Promise<void> {
  const query = new URLSearchParams()
  if (params.periodo) query.set('periodo', params.periodo)
  if (params.modulo) query.set('modulo', params.modulo)
  if (params.estado) query.set('estado', params.estado)
  const qs = query.toString()

  const token = getToken()
  const res = await fetch(`/api/dashboard/exportar-csv${qs ? `?${qs}` : ''}`, {
    headers: {
      Accept: 'text/csv',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
  })

  if (!res.ok) throw new Error('No se pudo exportar la bitácora.')

  const blob = await res.blob()
  const url = URL.createObjectURL(blob)
  const enlace = document.createElement('a')
  enlace.href = url
  enlace.download = 'bitacora.csv'
  document.body.appendChild(enlace)
  enlace.click()
  enlace.remove()
  URL.revokeObjectURL(url)
}
