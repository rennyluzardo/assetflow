import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api, ApiError } from '@/lib/api'
import type {
  EquipoTrasladoBusqueda,
  RecibirTrasladoResponse,
  RegistrarTrasladoPayload,
  Traslado,
  TrasladoEquiposResponse,
  TrasladoListaParams,
  TrasladoListaResponse,
} from './types'

/** Listado paginado de traslados (con filtro opcional por estado). */
export function useTraslados(params: TrasladoListaParams) {
  return useQuery<TrasladoListaResponse, ApiError>({
    queryKey: ['traslados', params],
    queryFn: () => api.listarTraslados(params),
  })
}

/** Registro de un nuevo traslado en tránsito. */
export function useRegistrarTraslado() {
  const queryClient = useQueryClient()
  return useMutation<Traslado, ApiError, RegistrarTrasladoPayload>({
    mutationFn: (payload) => api.registrarTraslado(payload),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['traslados'] })
    },
  })
}

/** Recepción de un traslado en la sede física del operario. */
export function useRecibirTraslado() {
  const queryClient = useQueryClient()
  return useMutation<RecibirTrasladoResponse, ApiError, string>({
    mutationFn: (codigo) => api.recibirTraslado(codigo),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['traslados'] })
    },
  })
}

/** Listado de equipos asociados a un traslado (sin mutar estado). */
export function useEquiposTraslado(codigo: string | null) {
  return useQuery<TrasladoEquiposResponse, ApiError>({
    queryKey: ['traslados', 'equipos', codigo],
    queryFn: () => api.listarEquiposTraslado(codigo as string),
    enabled: codigo !== null && codigo.trim() !== '',
  })
}

/** Escaneo de una placa para validar pertenencia y sede en traslados. */
export function useBuscarEquipoTraslado() {
  return useMutation<EquipoTrasladoBusqueda, ApiError, string>({
    mutationFn: (placaId) => api.buscarEquipoTraslado(placaId),
  })
}
