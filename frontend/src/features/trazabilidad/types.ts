import type { EstadoEquipo } from '@/constants/equipos'

export type TipoEvento = 'inventario' | 'equipos' | 'soplado' | 'portatil'

/** Evento unificado de la línea de tiempo de un activo. */
export interface EventoTrazabilidad {
  tipo: TipoEvento
  estacion: string
  fecha: string
  analista: string | null
  ubicacion: string | null
  estado: EstadoEquipo | null
  /** Evidencias ya resueltas a URL pública por el backend (`/storage/...`). */
  fotos?: string[]
  /** Ruta cruda heredada; se conserva por compatibilidad con clientes previos. */
  foto: string | null
  /** Campos individuales heredados que se normalizan cuando `fotos` no viene. */
  foto_url?: string | null
  foto_equipo?: string | null
  foto_ruta?: string | null
  detalle: string | null
  observaciones: string | null
}

/** Ficha maestra del activo (Identificador 1 / 2 y estado actual). */
export interface ActivoTrazabilidad {
  placa_id: string
  serial: string
  marca: string | null
  referencia: string | null
  descripcion: string
  zona_actual: string | null
  num_traslado: string
  ubicacion_actual: string | null
  estado_actual: EstadoEquipo | null
  intervenido: boolean
  fecha_intervencion: string | null
}

/** Envoltorio de `GET /api/trazabilidad?q=...`. */
export interface TrazabilidadResponse {
  activo: ActivoTrazabilidad
  eventos: EventoTrazabilidad[]
}
