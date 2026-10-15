import { useMutation } from '@tanstack/react-query'
import { api, ApiError } from '@/lib/api'
import type {
  EquipoRegistro,
  InventarioBusqueda,
  RegistrarEquipoPayload,
  TransicionarEquipoPayload,
} from '@/types/equipos'

/** Consulta de inventario al escanear una placa (on-demand). */
export function useBuscarEquipo() {
  return useMutation<InventarioBusqueda, ApiError, string>({
    mutationFn: (placaId) => api.buscarEquipo(placaId),
  })
}

/** Registro de una nueva gestión de CPU. */
export function useRegistrarEquipo() {
  return useMutation<EquipoRegistro, ApiError, RegistrarEquipoPayload>({
    mutationFn: (payload) => api.registrarEquipo(payload),
  })
}

/** Cierre/reanudación de un flujo intermedio (PENDIENTE_GARANTIA / POR_REACONDICIONAR). */
export function useTransicionarEquipo() {
  return useMutation<EquipoRegistro, ApiError, TransicionarEquipoPayload>({
    mutationFn: (payload) => api.transicionarEquipo(payload),
  })
}
