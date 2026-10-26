import type { EstadoEquipo, UbicacionDestino } from '@/constants/equipos'

/**
 * Espejo tipado de los enums del backend del módulo de Portátiles:
 *  - ClasificacionPortatilEnum
 *  - GravedadNovedadEnum
 *  - OrigenDanoGarantiaEnum
 *  - CausalBajaEnum
 */

export const CLASIFICACIONES = ['FUNCIONAL', 'NOVEDAD', 'GARANTIA', 'DANO_REPARABLE', 'BAJA'] as const
export type ClasificacionPortatil = (typeof CLASIFICACIONES)[number]

export const CLASIFICACION_LABELS: Record<ClasificacionPortatil, string> = {
  FUNCIONAL: 'Funcional',
  NOVEDAD: 'Novedad',
  GARANTIA: 'Garantía',
  DANO_REPARABLE: 'Daño reparable',
  BAJA: 'Baja',
}

export const GRAVEDADES = ['MINIMA', 'PRONUNCIADA'] as const
export type GravedadNovedad = (typeof GRAVEDADES)[number]

export const GRAVEDAD_LABELS: Record<GravedadNovedad, string> = {
  MINIMA: 'Mínima',
  PRONUNCIADA: 'Pronunciada',
}

export const ORIGENES = ['FABRICA', 'INDUCIDO'] as const
export type OrigenDanoGarantia = (typeof ORIGENES)[number]

export const ORIGEN_LABELS: Record<OrigenDanoGarantia, string> = {
  FABRICA: 'Fábrica',
  INDUCIDO: 'Inducido',
}

export const CAUSALES = ['OBSOLETO', 'DANO_TOTAL'] as const
export type CausalBaja = (typeof CAUSALES)[number]

export const CAUSAL_LABELS: Record<CausalBaja, string> = {
  OBSOLETO: 'Obsoleto',
  DANO_TOTAL: 'Daño total',
}

/** Registro único abierto devuelto por `GET /api/portatiles/buscar/{placaId}`. */
export interface PortatilActivo {
  id: number
  estado: EstadoEquipo
  clasificacion: ClasificacionPortatil
}

/** Respuesta de búsqueda de portátil (espejo de `InventarioBusqueda` + registro único). */
export interface PortatilBusqueda {
  placa_id: string
  serial: string
  referencia: string | null
  descripcion: string
  num_traslado: string
  ubicacion_actual: UbicacionDestino | null
  estado_actual: EstadoEquipo | null
  portatil_activo: PortatilActivo | null
}

/** Registro `garantia_portatil` devuelto por el backend tras guardar/cerrar. */
export interface PortatilRegistro {
  id: number
  placa_id: string
  serial: string
  clasificacion: ClasificacionPortatil
  ubicacion_destino: UbicacionDestino
  estado: EstadoEquipo
  reparado_por: string | null
  foto_evidencia: string | null
}

/** Cuerpo de `POST /api/portatiles`. */
export interface RegistrarPortatilPayload {
  placa_id: string
  clasificacion: ClasificacionPortatil
  test_lenovo: boolean
  serial_disco: string
  gravedad_novedad?: GravedadNovedad
  origen_dano_garantia?: OrigenDanoGarantia
  causal_baja?: CausalBaja
  cantidad_danos?: number
  ticket_proveedor?: string
  partes_fru?: string
  piezas_solicitar?: string
  ubicacion_destino: UbicacionDestino
  observaciones?: string
}

/** Cuerpo de `PATCH /api/portatiles/{id}/cerrar`. */
export interface CerrarPortatilPayload {
  ubicacion_destino: UbicacionDestino
  reparado_por: string
  foto_evidencia?: string
}
