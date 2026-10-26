import { useMutation, useQueryClient } from '@tanstack/react-query'
import { api, ApiError } from '@/lib/api'
import type {
  CerrarPortatilPayload,
  PortatilBusqueda,
  PortatilRegistro,
  RegistrarPortatilPayload,
} from './types'

/** Consulta del registro único de portátil al escanear una placa (on-demand). */
export function useBuscarPortatil() {
  return useMutation<PortatilBusqueda, ApiError, string>({
    mutationFn: (placaId) => api.buscarPortatil(placaId),
  })
}

/** Diagnóstico inicial de portátil (crea el registro único). */
export function useRegistrarPortatil() {
  const queryClient = useQueryClient()

  return useMutation<PortatilRegistro, ApiError, RegistrarPortatilPayload>({
    mutationFn: (payload) => api.registrarPortatil(payload),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['inventario'] })
      void queryClient.invalidateQueries({ queryKey: ['portatiles'] })
    },
  })
}

/** Cierre del registro único hacia Almacén General. */
export function useCerrarPortatil() {
  const queryClient = useQueryClient()

  return useMutation<PortatilRegistro, ApiError, { id: number; payload: CerrarPortatilPayload }>({
    mutationFn: ({ id, payload }) => api.cerrarPortatil(id, payload),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['inventario'] })
      void queryClient.invalidateQueries({ queryKey: ['portatiles'] })
    },
  })
}

/** Subida asíncrona de evidencia fotográfica (nombre UUID en backend). */
export function useSubirFotoPortatil() {
  return useMutation<PortatilRegistro, ApiError, { id: number; file: File }>({
    mutationFn: ({ id, file }) => api.subirFotoPortatil(id, file),
  })
}
