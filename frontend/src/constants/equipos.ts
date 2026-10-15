/**
 * Espejo tipado de los enums del backend (App\Enums\*).
 * Mantener en sincronía exacta con:
 *  - UbicacionDestinoEnum
 *  - EstadoEquipoEnum
 *  - TipoGestionEnum
 */

export const UBICACIONES = ['DBAN', 'ALMACEN', 'SALA_DE_GARANTIA', 'SALA_DE_BAJA'] as const
export type UbicacionDestino = (typeof UBICACIONES)[number]

export const ESTADOS = [
  'POR_SOPLAR',
  'PENDIENTE_GARANTIA',
  'POR_REACONDICIONAR',
  'COMPLETADO',
  'INTERVENIDO',
  'BAJA_NO_SIRVE',
  'REPARADO',
] as const
export type EstadoEquipo = (typeof ESTADOS)[number]

export const TIPOS_GESTION = ['NOVEDAD', 'DIAGNOSTICO', 'INTERVENCION'] as const
export type TipoGestion = (typeof TIPOS_GESTION)[number]

export const UBICACION_LABELS: Record<UbicacionDestino, string> = {
  DBAN: 'Dban',
  ALMACEN: 'Almacén',
  SALA_DE_GARANTIA: 'Sala de Garantía',
  SALA_DE_BAJA: 'Sala de Baja',
}

export const ESTADO_LABELS: Record<EstadoEquipo, string> = {
  POR_SOPLAR: 'Por soplar',
  PENDIENTE_GARANTIA: 'Pendiente garantía',
  POR_REACONDICIONAR: 'Por reacondicionar',
  COMPLETADO: 'Completado',
  INTERVENIDO: 'Intervenido',
  BAJA_NO_SIRVE: 'Baja (No sirve)',
  REPARADO: 'Reparado',
}

export const TIPO_GESTION_LABELS: Record<TipoGestion, string> = {
  NOVEDAD: 'Novedad',
  DIAGNOSTICO: 'Diagnóstico',
  INTERVENCION: 'Intervención',
}

/** Estados terminales: cierran el inventario (intervenido = 1). */
export const ESTADOS_TERMINALES: ReadonlySet<EstadoEquipo> = new Set([
  'COMPLETADO',
  'INTERVENIDO',
  'BAJA_NO_SIRVE',
  'REPARADO',
])

/** Estados intermedios: mantienen el flujo abierto (intervenido = 0). */
export const ESTADOS_INTERMEDIOS: ReadonlySet<EstadoEquipo> = new Set([
  'POR_SOPLAR',
  'PENDIENTE_GARANTIA',
  'POR_REACONDICIONAR',
])

/** Semántica visual nórdica por estado. */
export type EstadoSemantica = 'verde' | 'ambar' | 'terracota'

export const ESTADO_SEMANTICA: Record<EstadoEquipo, EstadoSemantica> = {
  COMPLETADO: 'verde',
  INTERVENIDO: 'verde',
  REPARADO: 'verde',
  POR_SOPLAR: 'ambar',
  PENDIENTE_GARANTIA: 'ambar',
  POR_REACONDICIONAR: 'ambar',
  BAJA_NO_SIRVE: 'terracota',
}

/** Clases Tailwind que materializan cada tono semántico (tokens del @theme). */
export const SEMANTICA_CLASSES: Record<EstadoSemantica, string> = {
  verde: 'bg-estado-verde text-estado-verde-ink border-estado-verde-line',
  ambar: 'bg-estado-ambar text-estado-ambar-ink border-estado-ambar-line',
  terracota: 'bg-estado-terracota text-estado-terracota-ink border-estado-terracota-line',
}
