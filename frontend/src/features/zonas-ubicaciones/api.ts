import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api, ApiError } from '@/lib/api'
import type { Ubicacion, Zona } from './types'

export function useZonasGestion() {
  return useQuery<Zona[], ApiError>({
    queryKey: ['zonas', 'gestion'],
    queryFn: () => api.zonasGestion(),
  })
}

function invalidar(queryClient: ReturnType<typeof useQueryClient>) {
  void queryClient.invalidateQueries({ queryKey: ['zonas', 'gestion'] })
  void queryClient.invalidateQueries({ queryKey: ['zonas', 'activas'] })
}

export function useCrearZona() {
  const queryClient = useQueryClient()
  return useMutation<Zona, ApiError, { sede: string; nombre: string }>({
    mutationFn: (payload) => api.crearZona(payload),
    onSuccess: () => invalidar(queryClient),
  })
}

export function useActualizarZona() {
  const queryClient = useQueryClient()
  return useMutation<Zona, ApiError, { id: number; sede: string; nombre: string }>({
    mutationFn: ({ id, sede, nombre }) => api.actualizarZona(id, { sede, nombre }),
    onSuccess: () => invalidar(queryClient),
  })
}

export function useAlternarZona() {
  const queryClient = useQueryClient()
  return useMutation<Zona, ApiError, { id: number; activo: boolean }>({
    mutationFn: ({ id, activo }) => (activo ? api.activarZona(id) : api.inactivarZona(id)),
    onSuccess: () => invalidar(queryClient),
  })
}

export function useCrearUbicacion() {
  const queryClient = useQueryClient()
  return useMutation<Ubicacion, ApiError, { zonaId: number; nombre: string }>({
    mutationFn: ({ zonaId, nombre }) => api.crearUbicacion(zonaId, { nombre }),
    onSuccess: () => invalidar(queryClient),
  })
}

export function useActualizarUbicacion() {
  const queryClient = useQueryClient()
  return useMutation<Ubicacion, ApiError, { id: number; zonaId: number; nombre: string }>({
    mutationFn: ({ id, zonaId, nombre }) =>
      api.actualizarUbicacion(id, { zona_id: zonaId, nombre }),
    onSuccess: () => invalidar(queryClient),
  })
}

export function useAlternarUbicacion() {
  const queryClient = useQueryClient()
  return useMutation<Ubicacion, ApiError, { id: number; activo: boolean }>({
    mutationFn: ({ id, activo }) => (activo ? api.activarUbicacion(id) : api.inactivarUbicacion(id)),
    onSuccess: () => invalidar(queryClient),
  })
}
