import { useMutation } from '@tanstack/react-query'
import { api, ApiError } from '@/lib/api'
import type { TrazabilidadResponse } from './types'

/** Consulta de la hoja de vida del activo por placa o serial (on-demand). */
export function useBuscarTrazabilidad() {
  return useMutation<TrazabilidadResponse, ApiError, string>({
    mutationFn: (q) => api.buscarTrazabilidad(q),
  })
}
