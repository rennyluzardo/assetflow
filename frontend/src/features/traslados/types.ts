export type TrasladoEstado = 'ABIERTO' | 'EN_TRANSITO' | 'RECIBIDO'

export type { Ubicacion, Zona } from '@/features/zonas-ubicaciones/types'

export interface UsuarioRelacion {
  id: number
  name: string
}

export interface Traslado {
  id: number
  codigo: string
  zona_origen: string | null
  ubicacion_origen: string | null
  zona_destino: string
  ubicacion_destino: string
  estado: TrasladoEstado
  observaciones: string | null
  observacion_cierre_parcial: string | null
  creado_por_id: number | null
  despachado_por_id: number | null
  despachado_at: string | null
  recibido_por_id: number | null
  recibido_at: string | null
  equipos_total: number
  equipos_recibidos: number
  created_at: string
  updated_at: string
  creador?: UsuarioRelacion | null
  despachador?: UsuarioRelacion | null
  receptor?: UsuarioRelacion | null
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
  codigo?: string
  zona_origen?: string
  ubicacion_origen?: string
  zona_destino?: string
  ubicacion_destino?: string
  estado?: TrasladoEstado
}

/** Filtros editables desde la URL de `/traslados`. */
export interface TrasladoSearchParams {
  codigo: string
  estado: TrasladoEstado | ''
  per_page: number
  page: number
}

export interface RegistrarTrasladoPayload {
  zona_destino: string
  ubicacion_destino: string
  observaciones?: string
}

/** Fila pivote devuelta al agregar o verificar un equipo. */
export interface PivoteEquipo {
  id: number
  traslado_id: number
  inventario_id: number
  placa_id: string
  recibido: boolean
}

export interface EquipoTraslado {
  id: number
  placa_id: string
  serial: string | null
  marca: string | null
  referencia: string | null
  descripcion: string | null
  zona_origen: string | null
  ubicacion_origen: string | null
  recibido: boolean
  recibido_at: string | null
  recibido_por: string | null
}

export interface TrasladoDetalle {
  traslado: Traslado
  equipos: EquipoTraslado[]
}

export interface BusquedaEquipoTraslado {
  placa_id: string
  serial: string | null
  marca: string | null
  referencia: string | null
  descripcion: string | null
  zona_actual: string | null
  ubicacion_actual: string | null
  traslado_activo: { codigo: string; estado: TrasladoEstado } | null
}

export interface ImportacionResultado {
  agregados: number
  errores: { placa: string; error: string }[]
}
