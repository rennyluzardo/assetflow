import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api, ApiError } from '@/lib/api'
import type {
  BusquedaEquipoTraslado,
  ImportacionResultado,
  PivoteEquipo,
  RegistrarTrasladoPayload,
  Traslado,
  TrasladoDetalle,
  TrasladoListaParams,
  TrasladoListaResponse,
  Zona,
} from './types'

/** Claves de caché compartidas por todas las mutaciones del módulo. */
const KEYS = {
  lista: ['traslados'] as const,
  detalle: (id: number) => ['traslados', 'detalle', id] as const,
}

export function useTraslados(params: TrasladoListaParams) {
  return useQuery<TrasladoListaResponse, ApiError>({
    queryKey: [...KEYS.lista, params],
    queryFn: () => api.listarTraslados(params),
    placeholderData: keepPreviousData,
  })
}

export function useZonasActivas() {
  return useQuery<Zona[], ApiError>({
    queryKey: ['zonas', 'activas'],
    queryFn: () => api.zonasActivas(),
  })
}

export function useDetalleTraslado(id: number | null) {
  return useQuery<TrasladoDetalle, ApiError>({
    queryKey: id === null ? ['traslados', 'detalle'] : KEYS.detalle(id),
    queryFn: () => api.detalleTraslado(id as number),
    enabled: id !== null,
  })
}

/** Registro de un traslado ABIERTO (solo destino; origen se bautiza después). */
export function useRegistrarTraslado() {
  const queryClient = useQueryClient()
  return useMutation<Traslado, ApiError, RegistrarTrasladoPayload>({
    mutationFn: (payload) => api.registrarTraslado(payload),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: KEYS.lista })
    },
  })
}

function invalidarTraslado(queryClient: ReturnType<typeof useQueryClient>, id: number) {
  void queryClient.invalidateQueries({ queryKey: KEYS.lista })
  void queryClient.invalidateQueries({ queryKey: KEYS.detalle(id) })
}

export function useAgregarEquipo(id: number) {
  const queryClient = useQueryClient()
  return useMutation<PivoteEquipo, ApiError, string>({
    mutationFn: (placaId) => api.agregarEquipoTraslado(id, placaId),
    onSuccess: () => invalidarTraslado(queryClient, id),
  })
}

export function useImportarEquipos(id: number) {
  const queryClient = useQueryClient()
  return useMutation<ImportacionResultado, ApiError, File>({
    mutationFn: (archivo) => api.importarEquiposTraslado(id, archivo),
    onSuccess: () => invalidarTraslado(queryClient, id),
  })
}

export function useQuitarEquipo(id: number) {
  const queryClient = useQueryClient()
  return useMutation<void, ApiError, number>({
    mutationFn: (equipoId) => api.quitarEquipoTraslado(id, equipoId),
    onSuccess: () => invalidarTraslado(queryClient, id),
  })
}

export function useDespachar(id: number) {
  const queryClient = useQueryClient()
  return useMutation<Traslado, ApiError, void>({
    mutationFn: () => api.despacharTraslado(id),
    onSuccess: () => invalidarTraslado(queryClient, id),
  })
}

export function useVerificarEquipo(id: number) {
  const queryClient = useQueryClient()
  return useMutation<PivoteEquipo, ApiError, string>({
    mutationFn: (placaId) => api.verificarEquipoTraslado(id, placaId),
    onSuccess: () => invalidarTraslado(queryClient, id),
  })
}

export function useRecibir(id: number) {
  const queryClient = useQueryClient()
  return useMutation<Traslado, ApiError, void>({
    mutationFn: () => api.recibirTraslado(id),
    onSuccess: () => invalidarTraslado(queryClient, id),
  })
}

export function useRevertirApertura(id: number) {
  const queryClient = useQueryClient()
  return useMutation<Traslado, ApiError, void>({
    mutationFn: () => api.revertirTrasladoApertura(id),
    onSuccess: () => invalidarTraslado(queryClient, id),
  })
}

export function useBuscarEquipoTraslado() {
  return useMutation<BusquedaEquipoTraslado, ApiError, string>({
    mutationFn: (placaId) => api.buscarEquipoTraslado(placaId),
  })
}
