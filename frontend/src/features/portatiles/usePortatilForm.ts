import { useState } from 'react'
import type { EstadoEquipo, UbicacionDestino } from '@/constants/equipos'
import type {
  CausalBaja,
  ClasificacionPortatil,
  DanoRow,
  GravedadNovedad,
  PortatilBusqueda,
  PortatilRegistro,
  RegistrarPortatilPayload,
  SubtipoIntervencion,
} from './types'
import { ApiError } from '@/lib/api'
import { evaluarPortatil } from './portatilFlowRules'
import type { CamposPortatil } from './portatilFlowRules'
import { useRegistrarPortatil } from './api'

interface UsePortatilFormOptions {
  portatil: PortatilBusqueda
  onGuardado?: (registro: PortatilRegistro) => void
}

export interface PortatilFormApi {
  clasificacion: ClasificacionPortatil | null
  setClasificacion: (c: ClasificacionPortatil) => void
  testLenovo: boolean
  setTestLenovo: (v: boolean) => void
  serialDisco: string
  setSerialDisco: (v: string) => void
  gravedadNovedad: GravedadNovedad | null
  setGravedadNovedad: (v: GravedadNovedad) => void
  causalBaja: CausalBaja | null
  setCausalBaja: (v: CausalBaja) => void
  danos: DanoRow[]
  agregarDano: () => void
  eliminarDano: (index: number) => void
  actualizarDano: (index: number, campo: 'pieza' | 'fru', valor: string) => void
  ticketProveedor: string
  setTicketProveedor: (v: string) => void
  piezasSolicitar: string
  setPiezasSolicitar: (v: string) => void
  piezaEsNueva: boolean
  setPiezaEsNueva: (v: boolean) => void
  subtipoIntervencion: SubtipoIntervencion | null
  setSubtipoIntervencion: (v: SubtipoIntervencion) => void
  especificacionRepotenciacion: string
  setEspecificacionRepotenciacion: (v: string) => void
  observaciones: string
  setObservaciones: (v: string) => void
  campos: CamposPortatil
  destinos: UbicacionDestino[]
  estadoPreview: EstadoEquipo | null
  completa: boolean
  destino: UbicacionDestino | null
  setDestino: (v: UbicacionDestino) => void
  puedeGuardar: boolean
  guardar: () => void
  guardando: boolean
  errorGuardar: ApiError | null
}

const DANO_INICIAL: DanoRow[] = [{ pieza: '', fru: '' }]
export const MAX_DANOS = 4

/** Orquesta la Matriz de Clasificación de Laptops y deriva el flujo en vivo. */
export function usePortatilForm({ portatil, onGuardado }: UsePortatilFormOptions): PortatilFormApi {
  const [clasificacion, setClasificacion] = useState<ClasificacionPortatil | null>(null)
  const [testLenovo, setTestLenovo] = useState(false)
  const [serialDisco, setSerialDisco] = useState('')
  const [gravedadNovedad, setGravedadNovedad] = useState<GravedadNovedad | null>(null)
  const [causalBaja, setCausalBaja] = useState<CausalBaja | null>(null)
  const [danos, setDanos] = useState<DanoRow[]>(DANO_INICIAL)
  const [ticketProveedor, setTicketProveedor] = useState('')
  const [piezasSolicitar, setPiezasSolicitar] = useState('')
  const [piezaEsNueva, setPiezaEsNueva] = useState(false)
  const [subtipoIntervencion, setSubtipoIntervencion] = useState<SubtipoIntervencion | null>(null)
  const [especificacionRepotenciacion, setEspecificacionRepotenciacion] = useState('')
  const [observaciones, setObservaciones] = useState('')
  const [destino, setDestino] = useState<UbicacionDestino | null>(null)

  const registrar = useRegistrarPortatil()

  const esPronunciada = clasificacion === 'NOVEDAD' && gravedadNovedad === 'PRONUNCIADA'
  const danosPiezas = danos.map((d) => d.pieza.trim()).filter(Boolean)
  const danosFru = danos.map((d) => d.fru.trim()).filter(Boolean)
  const danosCompletos = danos.length > 0 && danos.every((d) => d.pieza.trim().length > 0)

  const campos: CamposPortatil = {
    gravedad_novedad: gravedadNovedad ?? undefined,
    causal_baja: causalBaja ?? undefined,
    cantidad_danos: esPronunciada ? danos.length : undefined,
    ticket_proveedor: ticketProveedor || undefined,
    partes_fru: esPronunciada ? (danosFru.join(', ') || undefined) : undefined,
    piezas_solicitar: esPronunciada
      ? danosCompletos
        ? danosPiezas.join(', ')
        : undefined
      : (piezasSolicitar || undefined),
    serial_disco: serialDisco || undefined,
    pieza_es_nueva: piezaEsNueva,
    subtipo_intervencion: subtipoIntervencion ?? undefined,
    especificacion_repotenciacion: especificacionRepotenciacion || undefined,
    observaciones: observaciones || undefined,
  }

  const evaluacion = evaluarPortatil(clasificacion, campos)
  const puedeGuardar = evaluacion.completa && destino !== null && !registrar.isPending

  const cambiarClasificacion = (c: ClasificacionPortatil) => {
    setClasificacion(c)
    setDestino(null)
    setGravedadNovedad(null)
    setCausalBaja(null)
    setDanos(DANO_INICIAL)
    setTicketProveedor('')
    setPiezasSolicitar('')
    setPiezaEsNueva(false)
    setSubtipoIntervencion(null)
    setEspecificacionRepotenciacion('')
  }

  const agregarDano = () => {
    setDanos((previas) => (previas.length >= MAX_DANOS ? previas : [...previas, { pieza: '', fru: '' }]))
  }

  const eliminarDano = (index: number) => {
    setDanos((previas) => (previas.length <= 1 ? previas : previas.filter((_, i) => i !== index)))
  }

  const actualizarDano = (index: number, campo: 'pieza' | 'fru', valor: string) => {
    setDanos((previas) => previas.map((d, i) => (i === index ? { ...d, [campo]: valor } : d)))
  }

  const guardar = () => {
    if (!clasificacion || !destino) return

    const payload: RegistrarPortatilPayload = {
      placa_id: portatil.placa_id,
      clasificacion,
      test_lenovo: testLenovo,
      ubicacion_destino: destino,
    }

    if (serialDisco.trim()) payload.serial_disco = serialDisco.trim()
    if (gravedadNovedad) payload.gravedad_novedad = gravedadNovedad
    if (causalBaja) payload.causal_baja = causalBaja

    if (esPronunciada) {
      payload.cantidad_danos = danos.length
      if (danosPiezas.length) payload.piezas_solicitar = danosPiezas.join(', ')
      if (danosFru.length) payload.partes_fru = danosFru.join(', ')
    } else if (piezasSolicitar.trim()) {
      payload.piezas_solicitar = piezasSolicitar.trim()
    }

    if (ticketProveedor.trim()) payload.ticket_proveedor = ticketProveedor.trim()
    payload.pieza_es_nueva = piezaEsNueva
    if (subtipoIntervencion) payload.subtipo_intervencion = subtipoIntervencion
    if (especificacionRepotenciacion.trim()) {
      payload.especificacion_repotenciacion = especificacionRepotenciacion.trim()
    }
    if (observaciones.trim()) payload.observaciones = observaciones.trim()

    registrar.mutate(payload, { onSuccess: (r) => onGuardado?.(r) })
  }

  return {
    clasificacion,
    setClasificacion: cambiarClasificacion,
    testLenovo,
    setTestLenovo,
    serialDisco,
    setSerialDisco,
    gravedadNovedad,
    setGravedadNovedad,
    causalBaja,
    setCausalBaja,
    danos,
    agregarDano,
    eliminarDano,
    actualizarDano,
    ticketProveedor,
    setTicketProveedor,
    piezasSolicitar,
    setPiezasSolicitar,
    piezaEsNueva,
    setPiezaEsNueva,
    subtipoIntervencion,
    setSubtipoIntervencion,
    especificacionRepotenciacion,
    setEspecificacionRepotenciacion,
    observaciones,
    setObservaciones,
    campos,
    destinos: evaluacion.destinos,
    estadoPreview: evaluacion.estado,
    completa: evaluacion.completa,
    destino,
    setDestino,
    puedeGuardar,
    guardar,
    guardando: registrar.isPending,
    errorGuardar: registrar.error,
  }
}
