import { useState } from 'react'
import type { EstadoEquipo, TipoGestion, UbicacionDestino } from '@/constants/equipos'
import type {
  EquipoRegistro,
  InventarioBusqueda,
  RegistrarEquipoPayload,
  TransicionarEquipoPayload,
} from '@/types/equipos'
import { ApiError } from '@/lib/api'
import { evaluarCierre, evaluarFlujo, esFlujoReanudable } from './cpuFlowRules'
import type { RespuestaClinicaKey, RespuestasClinicas } from './cpuFlowRules'
import { useRegistrarEquipo, useTransicionarEquipo } from './api'

interface UseEquipoFormOptions {
  equipo: InventarioBusqueda
  onGuardado?: (registro: EquipoRegistro) => void
}

export interface EquipoFormApi {
  reanudable: boolean
  estadoReanudacion: EstadoEquipo | null
  tipoGestion: TipoGestion | null
  setTipoGestion: (tipo: TipoGestion) => void
  respuestas: RespuestasClinicas
  responder: (clave: RespuestaClinicaKey, valor: boolean) => void
  preguntaActual: RespuestaClinicaKey | null
  destinos: UbicacionDestino[]
  destino: UbicacionDestino | null
  setDestino: (destino: UbicacionDestino) => void
  estadoPreview: EstadoEquipo | null
  completo: boolean
  observaciones: string
  setObservaciones: (value: string) => void
  guardar: () => void
  guardando: boolean
  errorGuardar: ApiError | null
}

/**
 * Orquesta el árbol de decisión clínico del formulario de `/equipos`.
 * Mantiene el estado (tipo de gestión, respuestas, destino) y deriva en vivo
 * la evaluación del flujo usando `cpuFlowRules`.
 */
export function useEquipoForm({ equipo, onGuardado }: UseEquipoFormOptions): EquipoFormApi {
  const estadoReanudacion = equipo.equipo_activo?.estado ?? null
  const reanudable = esFlujoReanudable(estadoReanudacion)

  const [tipoGestion, setTipoGestionState] = useState<TipoGestion | null>(null)
  const [respuestas, setRespuestas] = useState<RespuestasClinicas>({})
  const [destino, setDestinoState] = useState<UbicacionDestino | null>(null)
  const [observaciones, setObservaciones] = useState('')

  const registrar = useRegistrarEquipo()
  const transicionar = useTransicionarEquipo()

  const evaluacion = reanudable
    ? evaluarCierre(estadoReanudacion!, respuestas)
    : evaluarFlujo(tipoGestion, respuestas)

  const preguntaActual = evaluacion.pregunta

  const setTipoGestion = (tipo: TipoGestion) => {
    setTipoGestionState(tipo)
    setRespuestas({})
    setDestinoState(null)
  }

  const responder = (clave: RespuestaClinicaKey, valor: boolean) => {
    setRespuestas((previas) => ({ ...previas, [clave]: valor }))
    setDestinoState(null)
  }

  const guardar = () => {
    if (!evaluacion.completo || destino === null) return

    if (reanudable && estadoReanudacion) {
      const payload: TransicionarEquipoPayload = {
        placa_id: equipo.placa_id,
        ubicacion_destino: destino,
      }
      if (estadoReanudacion === 'POR_REACONDICIONAR') {
        payload.requiere_soplado_o_pasta = respuestas.requiere_soplado_o_pasta
      }
      transicionar.mutate(payload, { onSuccess: (registro) => onGuardado?.(registro) })
      return
    }

    const payload: RegistrarEquipoPayload = {
      placa_id: equipo.placa_id,
      tipo_gestion: tipoGestion!,
      ...respuestas,
      ubicacion_destino: destino,
      observaciones: observaciones.trim() ? observaciones.trim() : undefined,
    }
    registrar.mutate(payload, { onSuccess: (registro) => onGuardado?.(registro) })
  }

  return {
    reanudable,
    estadoReanudacion,
    tipoGestion,
    setTipoGestion,
    respuestas,
    responder,
    preguntaActual,
    destinos: evaluacion.destinos,
    destino,
    setDestino: setDestinoState,
    estadoPreview: evaluacion.estado,
    completo: evaluacion.completo,
    observaciones,
    setObservaciones,
    guardar,
    guardando: registrar.isPending || transicionar.isPending,
    errorGuardar: registrar.error ?? transicionar.error,
  }
}
