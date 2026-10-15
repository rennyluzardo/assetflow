import type { EstadoEquipo, TipoGestion, UbicacionDestino } from '@/constants/equipos'

/**
 * Motor de reglas frontend — espejo exacto del backend `CpuStateMachineService`.
 *
 * Permite al formulario de `/equipos` filtrar dinámicamente la ubicación de
 * destino y previsualizar el estado exacto con el que se guardará el registro,
 * *antes* de llamar al backend (que sigue siendo la fuente de verdad).
 */

export type RespuestaClinicaKey =
  | 'requiere_soplado_o_pasta'
  | 'esta_funcional'
  | 'tiene_garantia'
  | 'es_obsoleta'
  | 'hay_pieza'

export interface RespuestasClinicas {
  requiere_soplado_o_pasta?: boolean
  esta_funcional?: boolean
  tiene_garantia?: boolean
  es_obsoleta?: boolean
  hay_pieza?: boolean
}

export interface PreguntaClinica {
  id: RespuestaClinicaKey
  texto: string
}

export const CATALOGO_PREGUNTAS: Record<RespuestaClinicaKey, PreguntaClinica> = {
  requiere_soplado_o_pasta: {
    id: 'requiere_soplado_o_pasta',
    texto: '¿Está sucia o requiere cambio de pasta térmica?',
  },
  esta_funcional: { id: 'esta_funcional', texto: '¿Está funcional?' },
  tiene_garantia: { id: 'tiene_garantia', texto: '¿Tiene garantía?' },
  es_obsoleta: { id: 'es_obsoleta', texto: '¿Es obsoleta?' },
  hay_pieza: { id: 'hay_pieza', texto: '¿Hay la pieza?' },
}

function requerirBooleana(r: RespuestasClinicas, clave: RespuestaClinicaKey): boolean {
  const valor = r[clave]
  if (valor === undefined) {
    throw new Error(`La respuesta "${clave}" es obligatoria para este tipo de gestión.`)
  }
  return valor
}

/** Devuelve la siguiente pregunta pendiente, o `null` si el flujo está completo. */
export function siguientePregunta(
  tipo: TipoGestion | null,
  r: RespuestasClinicas,
): RespuestaClinicaKey | null {
  if (!tipo) return null

  switch (tipo) {
    case 'NOVEDAD':
      return r.requiere_soplado_o_pasta === undefined ? 'requiere_soplado_o_pasta' : null

    case 'DIAGNOSTICO':
      if (r.esta_funcional === undefined) return 'esta_funcional'
      if (r.esta_funcional) {
        if (r.es_obsoleta === undefined) return 'es_obsoleta'
        if (r.es_obsoleta) return null
        return r.requiere_soplado_o_pasta === undefined ? 'requiere_soplado_o_pasta' : null
      }
      return r.tiene_garantia === undefined ? 'tiene_garantia' : null

    case 'INTERVENCION':
      if (r.hay_pieza === undefined) return 'hay_pieza'
      if (!r.hay_pieza) return null
      return r.requiere_soplado_o_pasta === undefined ? 'requiere_soplado_o_pasta' : null
  }
}

/** Ubicaciones de destino válidas para una rama del árbol (requiere flujo completo). */
export function destinosPermitidos(tipo: TipoGestion, r: RespuestasClinicas): UbicacionDestino[] {
  switch (tipo) {
    case 'NOVEDAD':
      return requerirBooleana(r, 'requiere_soplado_o_pasta')
        ? ['SALA_DE_GARANTIA']
        : ['DBAN', 'ALMACEN']

    case 'DIAGNOSTICO':
      if (!requerirBooleana(r, 'esta_funcional')) {
        return requerirBooleana(r, 'tiene_garantia') ? ['SALA_DE_GARANTIA'] : ['SALA_DE_BAJA']
      }
      if (requerirBooleana(r, 'es_obsoleta')) return ['SALA_DE_BAJA']
      return requerirBooleana(r, 'requiere_soplado_o_pasta')
        ? ['SALA_DE_GARANTIA']
        : ['DBAN', 'ALMACEN']

    case 'INTERVENCION':
      // En Intervención el destino siempre es Sala de Garantía.
      return ['SALA_DE_GARANTIA']
  }
}

/** Estado resultante de una rama del árbol (requiere flujo completo). */
export function resolverEstado(tipo: TipoGestion, r: RespuestasClinicas): EstadoEquipo {
  switch (tipo) {
    case 'NOVEDAD':
      return requerirBooleana(r, 'requiere_soplado_o_pasta') ? 'POR_SOPLAR' : 'COMPLETADO'

    case 'DIAGNOSTICO':
      if (!requerirBooleana(r, 'esta_funcional')) {
        return requerirBooleana(r, 'tiene_garantia') ? 'PENDIENTE_GARANTIA' : 'BAJA_NO_SIRVE'
      }
      if (requerirBooleana(r, 'es_obsoleta')) return 'BAJA_NO_SIRVE'
      return requerirBooleana(r, 'requiere_soplado_o_pasta') ? 'POR_SOPLAR' : 'COMPLETADO'

    case 'INTERVENCION':
      if (!requerirBooleana(r, 'hay_pieza')) return 'POR_REACONDICIONAR'
      return requerirBooleana(r, 'requiere_soplado_o_pasta') ? 'POR_SOPLAR' : 'INTERVENIDO'
  }
}

export interface Evaluacion {
  completo: boolean
  destinos: UbicacionDestino[]
  estado: EstadoEquipo | null
  pregunta: RespuestaClinicaKey | null
}

/** Evalúa el flujo de registro nuevo. `completo` habilita el selector de destino. */
export function evaluarFlujo(tipo: TipoGestion | null, r: RespuestasClinicas): Evaluacion {
  const pregunta = siguientePregunta(tipo, r)
  if (!tipo || pregunta !== null) {
    return { completo: false, destinos: [], estado: null, pregunta }
  }
  return {
    completo: true,
    destinos: destinosPermitidos(tipo, r),
    estado: resolverEstado(tipo, r),
    pregunta: null,
  }
}

/** Evalúa la reanudación/cierre de un flujo intermedio (PENDIENTE_GARANTIA o POR_REACONDICIONAR). */
export function evaluarCierre(estadoActual: EstadoEquipo, r: RespuestasClinicas): Evaluacion {
  switch (estadoActual) {
    case 'PENDIENTE_GARANTIA':
      return { completo: true, destinos: ['DBAN', 'ALMACEN'], estado: 'COMPLETADO', pregunta: null }

    case 'POR_REACONDICIONAR':
      if (r.requiere_soplado_o_pasta === undefined) {
        return { completo: false, destinos: [], estado: null, pregunta: 'requiere_soplado_o_pasta' }
      }
      return {
        completo: true,
        destinos: ['SALA_DE_GARANTIA'],
        estado: r.requiere_soplado_o_pasta ? 'POR_SOPLAR' : 'INTERVENIDO',
        pregunta: null,
      }

    default:
      return { completo: false, destinos: [], estado: null, pregunta: null }
  }
}

const ESTADOS_REANUDABLES: ReadonlySet<EstadoEquipo> = new Set([
  'PENDIENTE_GARANTIA',
  'POR_REACONDICIONAR',
])

export function esFlujoReanudable(estado: EstadoEquipo | null | undefined): boolean {
  return estado != null && ESTADOS_REANUDABLES.has(estado)
}
