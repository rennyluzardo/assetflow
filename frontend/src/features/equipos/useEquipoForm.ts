import { useState } from 'react'
import type { EstadoEquipo, TipoGestion, UbicacionDestino } from '@/constants/equipos'
import type {
  EquipoRegistro,
  InventarioBusqueda,
  RegistrarEquipoPayload,
  TransicionarEquipoPayload,
} from '@/types/equipos'
import { ApiError } from '@/lib/api'
import { evaluarCierre, evaluarFlujo, esFlujoReanudable, fotoEquipoRequerida } from './cpuFlowRules'
import type { CampoTextoKey, RespuestaClinicaKey, RespuestasClinicas } from './cpuFlowRules'
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
  textosRequeridos: CampoTextoKey[]
  textos: Record<CampoTextoKey, string>
  setTexto: (clave: CampoTextoKey, valor: string) => void
  destinos: UbicacionDestino[]
  destino: UbicacionDestino | null
  setDestino: (destino: UbicacionDestino) => void
  estadoPreview: EstadoEquipo | null
  completo: boolean
  observaciones: string
  setObservaciones: (value: string) => void
  observacionesRequeridas: boolean
  foto: File | null
  setFoto: (file: File | null) => void
  fotoRequerida: boolean
  puedeGuardar: boolean
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
  const [textos, setTextos] = useState<Record<CampoTextoKey, string>>({
    motivo_baja: '',
    serial_disco: '',
    ticket_garantia: '',
    pieza_garantia: '',
  })
  const [destino, setDestinoState] = useState<UbicacionDestino | null>(null)
  const [observaciones, setObservaciones] = useState('')
  const [foto, setFoto] = useState<File | null>(null)

  const registrar = useRegistrarEquipo()
  const transicionar = useTransicionarEquipo()

  const evaluacion = reanudable
    ? evaluarCierre(estadoReanudacion!, respuestas)
    : evaluarFlujo(tipoGestion, respuestas, textos)

  const preguntaActual = evaluacion.pregunta
  const observacionesRequeridas = tipoGestion === 'NOVEDAD'
  const observacionesCompletas = !observacionesRequeridas || observaciones.trim() !== ''
  const fotoRequerida = fotoEquipoRequerida(tipoGestion)
  const fotoCompleta = !fotoRequerida || foto !== null

  const puedeGuardar =
    evaluacion.completo && destino !== null && observacionesCompletas && fotoCompleta

  const setTipoGestion = (tipo: TipoGestion) => {
    setTipoGestionState(tipo)
    setRespuestas({})
    setTextos({ motivo_baja: '', serial_disco: '', ticket_garantia: '', pieza_garantia: '' })
    setDestinoState(null)
    setObservaciones('')
    setFoto(null)
  }

  const responder = (clave: RespuestaClinicaKey, valor: boolean) => {
    setRespuestas((previas) => ({ ...previas, [clave]: valor }))
    setDestinoState(null)
  }

  const setTexto = (clave: CampoTextoKey, valor: string) => {
    setTextos((previos) => ({ ...previos, [clave]: valor }))
    setDestinoState(null)
  }

  const guardar = () => {
    if (!evaluacion.completo || destino === null || !observacionesCompletas) return

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

    if (textos.motivo_baja.trim()) payload.motivo_baja = textos.motivo_baja.trim()
    if (textos.serial_disco.trim()) payload.serial_disco = textos.serial_disco.trim()
    if (textos.ticket_garantia.trim()) payload.ticket_garantia = textos.ticket_garantia.trim()
    if (textos.pieza_garantia.trim()) payload.pieza_garantia = textos.pieza_garantia.trim()
    if (foto) payload.foto_equipo = foto

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
    textosRequeridos: evaluacion.textosRequeridos,
    textos,
    setTexto,
    destinos: evaluacion.destinos,
    destino,
    setDestino: setDestinoState,
    estadoPreview: evaluacion.estado,
    completo: evaluacion.completo,
    observaciones,
    setObservaciones,
    observacionesRequeridas,
    foto,
    setFoto,
    fotoRequerida,
    puedeGuardar,
    guardar,
    guardando: registrar.isPending || transicionar.isPending,
    errorGuardar: registrar.error ?? transicionar.error,
  }
}
