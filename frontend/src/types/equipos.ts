import type { EstadoEquipo, TipoGestion, UbicacionDestino } from '@/constants/equipos'

/** Respuesta de `GET /api/equipos/buscar/{placaId}` (ya desempaquetada de `data`). */
export interface InventarioBusqueda {
  placa_id: string
  serial: string
  referencia: string | null
  descripcion: string
  num_traslado: string
  ubicacion_actual: UbicacionDestino | null
  estado_actual: EstadoEquipo | null
  equipo_activo: EquipoActivo | null
}

export interface EquipoActivo {
  id: number
  estado: EstadoEquipo
  tipo_gestion: TipoGestion
}

/** Registro de `equipos` devuelto por el backend tras guardar. */
export interface EquipoRegistro {
  id: number
  placa_id: string
  serial: string
  tipo_gestion: TipoGestion
  ubicacion_destino: UbicacionDestino
  estado: EstadoEquipo
}

/** Cuerpo de `POST /api/equipos`. */
export interface RegistrarEquipoPayload {
  placa_id: string
  tipo_gestion: TipoGestion
  requiere_soplado_o_pasta?: boolean
  esta_funcional?: boolean
  tiene_garantia?: boolean
  es_obsoleta?: boolean
  hay_pieza?: boolean
  ubicacion_destino: UbicacionDestino
  foto_equipo?: string
  observaciones?: string
}

/** Cuerpo de `POST /api/equipos/transicionar`. */
export interface TransicionarEquipoPayload {
  placa_id: string
  ubicacion_destino: UbicacionDestino
  requiere_soplado_o_pasta?: boolean
}
