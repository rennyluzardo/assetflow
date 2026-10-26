import type { EstadoEquipo, UbicacionDestino } from '@/constants/equipos'

export type TipoEvento = 'inventario' | 'equipos' | 'soplado' | 'portatil'

/** Evento unificado de la línea de tiempo de un activo. */
export interface EventoTrazabilidad {
  tipo: TipoEvento
  estacion: string
  fecha: string
  analista: string | null
  ubicacion: UbicacionDestino | null
  estado: EstadoEquipo | null
  foto: string | null
  detalle: string | null
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
  ubicacion_actual: UbicacionDestino | null
  estado_actual: EstadoEquipo | null
  intervenido: boolean
  fecha_intervencion: string | null
}

/** Envoltorio de `GET /api/trazabilidad?q=...`. */
export interface TrazabilidadResponse {
  activo: ActivoTrazabilidad
  eventos: EventoTrazabilidad[]
}
