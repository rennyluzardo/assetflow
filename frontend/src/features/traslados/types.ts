export type ZonaSede = 'BOGOTA' | 'MEDELLIN'
export type TrasladoEstado = 'EN_TRANSITO' | 'RECIBIDO'

export interface Traslado {
  id: number
  codigo: string
  zona_origen: ZonaSede
  ubicacion_origen_exacta: string | null
  zona_destino: ZonaSede
  ubicacion_destino_exacta: string | null
  estado: TrasladoEstado
  analista_id: number | null
  equipos_count: number
  created_at: string
  updated_at: string
}

export interface TrasladoMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface TrasladoListaResponse {
  data: Traslado[]
  meta: TrasladoMeta
}

export interface TrasladoListaParams {
  page?: number
  per_page?: number
  estado?: TrasladoEstado
}

export interface RegistrarTrasladoPayload {
  codigo: string
  zona_origen: ZonaSede
  ubicacion_origen_exacta?: string
  zona_destino: ZonaSede
  ubicacion_destino_exacta?: string
}

/** Activo devuelto al escanear una placa en el módulo de traslados. */
export interface EquipoTrasladoBusqueda {
  placa_id: string
  serial: string
  marca: string | null
  referencia: string | null
  descripcion: string
  num_traslado: string
  ubicacion_origen: string | null
}

export interface EquipoRecibido {
  id: number
  placa_id: string
  serial: string
  marca: string | null
  referencia: string | null
  descripcion: string
  num_traslado: string
  ubicacion_actual: string | null
  estado_actual: string | null
  intervenido: boolean
  ubicacion_origen: string | null
}

export interface RecibirTrasladoResponse {
  traslado: Traslado
  equipos: EquipoRecibido[]
}

/** Listado de equipos asociados a un traslado (vista unificada de equipos). */
export interface TrasladoEquiposResponse {
  traslado: Traslado | null
  equipos: EquipoRecibido[]
}
