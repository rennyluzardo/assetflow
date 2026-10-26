import type { EstadoEquipo, UbicacionDestino } from '@/constants/equipos'
import type {
  CausalBaja,
  ClasificacionPortatil,
  GravedadNovedad,
  OrigenDanoGarantia,
} from './types'

/**
 * Motor de reglas frontend — espejo exacto del backend
 * `PortatilStateMachineService`.
 *
 * Permite al formulario de `/portatiles` mostrar dinámicamente los campos
 * requeridos de la Matriz de Clasificación de Laptops, filtrar el destino y
 * previsualizar el estado antes de llamar al backend (fuente de verdad).
 */

export interface CamposPortatil {
  gravedad_novedad?: GravedadNovedad
  origen_dano_garantia?: OrigenDanoGarantia
  causal_baja?: CausalBaja
  cantidad_danos?: number
  ticket_proveedor?: string
  partes_fru?: string
  piezas_solicitar?: string
}

function esTexto(valor: unknown): valor is string {
  return typeof valor === 'string' && valor.trim().length > 0
}

/** Claves de los sub-campos del árbol aún pendientes para completar la rama. */
export function camposFaltantes(clasificacion: ClasificacionPortatil, c: CamposPortatil): string[] {
  const faltantes: string[] = []

  switch (clasificacion) {
    case 'FUNCIONAL':
      break

    case 'NOVEDAD':
      if (c.gravedad_novedad === undefined) {
        faltantes.push('gravedad_novedad')
      } else if (c.gravedad_novedad === 'PRONUNCIADA') {
        if (c.cantidad_danos === undefined || c.cantidad_danos <= 1) faltantes.push('cantidad_danos')
        if (!esTexto(c.piezas_solicitar)) faltantes.push('piezas_solicitar')
      }
      break

    case 'GARANTIA':
      if (c.origen_dano_garantia === undefined) {
        faltantes.push('origen_dano_garantia')
      } else if (c.origen_dano_garantia === 'FABRICA') {
        if (!esTexto(c.ticket_proveedor)) faltantes.push('ticket_proveedor')
      } else if (!esTexto(c.piezas_solicitar)) {
        faltantes.push('piezas_solicitar')
      }
      break

    case 'DANO_REPARABLE':
      if (c.cantidad_danos === undefined || c.cantidad_danos > 3) faltantes.push('cantidad_danos')
      if (!esTexto(c.piezas_solicitar)) faltantes.push('piezas_solicitar')
      // `partes_fru` es opcional (no todos los portátiles tienen código FRU de Lenovo).
      break

    case 'BAJA':
      if (c.causal_baja === undefined) faltantes.push('causal_baja')
      break
  }

  return faltantes
}

/** Destino obligatorio de la rama (siempre único en portátiles). */
export function destinosPermitidos(
  clasificacion: ClasificacionPortatil,
  c: CamposPortatil,
): UbicacionDestino[] {
  switch (clasificacion) {
    case 'FUNCIONAL':
      return ['ALMACEN']
    case 'NOVEDAD':
      return c.gravedad_novedad === 'PRONUNCIADA' ? ['SALA_DE_GARANTIA'] : ['ALMACEN']
    case 'GARANTIA':
    case 'DANO_REPARABLE':
      return ['SALA_DE_GARANTIA']
    case 'BAJA':
      return ['SALA_DE_BAJA']
  }
}

/** Estado automático resultante de la rama (requiere campos completos). */
export function resolverEstado(
  clasificacion: ClasificacionPortatil,
  c: CamposPortatil,
): EstadoEquipo {
  switch (clasificacion) {
    case 'FUNCIONAL':
      return 'COMPLETADO'
    case 'NOVEDAD':
      return c.gravedad_novedad === 'PRONUNCIADA' ? 'POR_REACONDICIONAR' : 'COMPLETADO'
    case 'GARANTIA':
      return c.origen_dano_garantia === 'FABRICA' ? 'PENDIENTE_GARANTIA' : 'POR_REACONDICIONAR'
    case 'DANO_REPARABLE':
      return 'POR_REACONDICIONAR'
    case 'BAJA':
      return 'BAJA_NO_SIRVE'
  }
}

export interface EvaluacionPortatil {
  completa: boolean
  destinos: UbicacionDestino[]
  estado: EstadoEquipo | null
}

/** Evalúa el diagnóstico nuevo. `completa` habilita el selector de destino. */
export function evaluarPortatil(
  clasificacion: ClasificacionPortatil | null,
  c: CamposPortatil,
): EvaluacionPortatil {
  if (!clasificacion || camposFaltantes(clasificacion, c).length > 0) {
    return { completa: false, destinos: [], estado: null }
  }
  return {
    completa: true,
    destinos: destinosPermitidos(clasificacion, c),
    estado: resolverEstado(clasificacion, c),
  }
}

const ESTADOS_CIERRE: ReadonlySet<EstadoEquipo> = new Set([
  'PENDIENTE_GARANTIA',
  'POR_REACONDICIONAR',
])

export function esRegistroCerrable(estado: EstadoEquipo | null | undefined): boolean {
  return estado != null && ESTADOS_CIERRE.has(estado)
}
