import { useMutation } from '@tanstack/react-query'
import { api, ApiError } from '@/lib/api'
import type { InventarioBusqueda } from '@/types/equipos'
import type { RegistrarSopladoPayload, SopladoRegistroResult } from './types'

/** Consulta del flujo activo de soplado al escanear una placa (on-demand). */
export function useBuscarSoplado() {
  return useMutation<InventarioBusqueda, ApiError, string>({
    mutationFn: (placaId) => api.buscarSoplado(placaId),
  })
}

/** Registro de limpieza y despacho final desde el módulo /soplado. */
export function useRegistrarSoplado() {
  return useMutation<SopladoRegistroResult, ApiError, RegistrarSopladoPayload>({
    mutationFn: (payload) => api.registrarSoplado(payload),
  })
}
