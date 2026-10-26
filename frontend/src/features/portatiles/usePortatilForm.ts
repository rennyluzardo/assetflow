import { useMemo, useState } from 'react'
import type { EstadoEquipo, UbicacionDestino } from '@/constants/equipos'
import type {
  CausalBaja,
  ClasificacionPortatil,
  GravedadNovedad,
  OrigenDanoGarantia,
  PortatilBusqueda,
  PortatilRegistro,
  RegistrarPortatilPayload,
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
  origenDanoGarantia: OrigenDanoGarantia | null
  setOrigenDanoGarantia: (v: OrigenDanoGarantia) => void
  causalBaja: CausalBaja | null
  setCausalBaja: (v: CausalBaja) => void
  cantidadDanos: string
  setCantidadDanos: (v: string) => void
  ticketProveedor: string
  setTicketProveedor: (v: string) => void
  partesFru: string
  setPartesFru: (v: string) => void
  piezasSolicitar: string
  setPiezasSolicitar: (v: string) => void
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

/** Orquesta la Matriz de Clasificación de Laptops y deriva el flujo en vivo. */
export function usePortatilForm({ portatil, onGuardado }: UsePortatilFormOptions): PortatilFormApi {
  const [clasificacion, setClasificacion] = useState<ClasificacionPortatil | null>(null)
  const [testLenovo, setTestLenovo] = useState(false)
  const [serialDisco, setSerialDisco] = useState('')
  const [gravedadNovedad, setGravedadNovedad] = useState<GravedadNovedad | null>(null)
  const [origenDanoGarantia, setOrigenDanoGarantia] = useState<OrigenDanoGarantia | null>(null)
  const [causalBaja, setCausalBaja] = useState<CausalBaja | null>(null)
  const [cantidadDanos, setCantidadDanos] = useState('')
  const [ticketProveedor, setTicketProveedor] = useState('')
  const [partesFru, setPartesFru] = useState('')
  const [piezasSolicitar, setPiezasSolicitar] = useState('')
  const [observaciones, setObservaciones] = useState('')
  const [destino, setDestino] = useState<UbicacionDestino | null>(null)

  const registrar = useRegistrarPortatil()

  const campos = useMemo<CamposPortatil>(
    () => ({
      gravedad_novedad: gravedadNovedad ?? undefined,
      origen_dano_garantia: origenDanoGarantia ?? undefined,
      causal_baja: causalBaja ?? undefined,
      cantidad_danos: cantidadDanos === '' ? undefined : Number(cantidadDanos),
      ticket_proveedor: ticketProveedor || undefined,
      partes_fru: partesFru || undefined,
      piezas_solicitar: piezasSolicitar || undefined,
    }),
    [gravedadNovedad, origenDanoGarantia, causalBaja, cantidadDanos, ticketProveedor, partesFru, piezasSolicitar],
  )

  const evaluacion = evaluarPortatil(clasificacion, campos)
  const puedeGuardar = evaluacion.completa && destino !== null && serialDisco.trim() !== '' && !registrar.isPending

  const cambiarClasificacion = (c: ClasificacionPortatil) => {
    setClasificacion(c)
    setDestino(null)
    setGravedadNovedad(null)
    setOrigenDanoGarantia(null)
    setCausalBaja(null)
    setCantidadDanos('')
    setTicketProveedor('')
    setPartesFru('')
    setPiezasSolicitar('')
  }

  const guardar = () => {
    if (!clasificacion || !destino || serialDisco.trim() === '') return

    const payload: RegistrarPortatilPayload = {
      placa_id: portatil.placa_id,
      clasificacion,
      test_lenovo: testLenovo,
      serial_disco: serialDisco.trim(),
      ubicacion_destino: destino,
    }

    if (gravedadNovedad) payload.gravedad_novedad = gravedadNovedad
    if (origenDanoGarantia) payload.origen_dano_garantia = origenDanoGarantia
    if (causalBaja) payload.causal_baja = causalBaja
    if (cantidadDanos !== '') payload.cantidad_danos = Number(cantidadDanos)
    if (ticketProveedor.trim()) payload.ticket_proveedor = ticketProveedor.trim()
    if (partesFru.trim()) payload.partes_fru = partesFru.trim()
    if (piezasSolicitar.trim()) payload.piezas_solicitar = piezasSolicitar.trim()
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
    origenDanoGarantia,
    setOrigenDanoGarantia,
    causalBaja,
    setCausalBaja,
    cantidadDanos,
    setCantidadDanos,
    ticketProveedor,
    setTicketProveedor,
    partesFru,
    setPartesFru,
    piezasSolicitar,
    setPiezasSolicitar,
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
