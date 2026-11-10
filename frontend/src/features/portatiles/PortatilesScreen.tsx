import { useState } from 'react'
import type { PortatilActivo, PortatilBusqueda, PortatilRegistro } from './types'
import { ESTADO_LABELS } from '@/constants/equipos'
import { ApiError } from '@/lib/api'
import { ScannerInput } from '@/components/ScannerInput'
import type { ScannerStatus } from '@/components/ScannerInput'
import { StatusBadge } from '@/components/StatusBadge'
import { EquipoResumen } from '@/features/equipos/EquipoResumen'
import { useAuth } from '@/features/auth/AuthContext'
import { useBuscarPortatil } from './api'
import { esRegistroCerrable } from './portatilFlowRules'
import { PortatilForm } from './PortatilForm'
import { CierrePortatilModal } from './CierrePortatilModal'

const MENSAJE_REINTERVENCION =
  'Este equipo ya fue intervenido. Solo un administrador puede autorizar una nueva gestión.'

/**
 * Veredicto del escaneo para la estación de portátiles:
 *  - verde  → nuevo ingreso (sin registro único abierto);
 *  - ámbar  → registro único abierto (PENDIENTE_GARANTIA / POR_REACONDICIONAR);
 *  - terracota → ya intervenido (cerrado en un estado terminal).
 */
function veredicto(
  portatil: PortatilBusqueda,
  esAdmin: boolean,
): {
  status: ScannerStatus
  message: string
  procesable: boolean
} {
  const activo = portatil.portatil_activo

  if (!activo) {
    return { status: 'success', message: 'Portátil válido — nuevo ingreso.', procesable: true }
  }

  if (esRegistroCerrable(activo.estado)) {
    return {
      status: 'warning',
      message: 'Registro único abierto — pendiente de cierre.',
      procesable: false,
    }
  }

  if (esAdmin) {
    return {
      status: 'warning',
      message: `Portátil ya intervenido (${ESTADO_LABELS[activo.estado]}). Puede autorizar una nueva gestión.`,
      procesable: true,
    }
  }

  return {
    status: 'error',
    message: MENSAJE_REINTERVENCION,
    procesable: false,
  }
}

export function PortatilesScreen() {
  const { session } = useAuth()
  const esAdmin = session?.roles.includes('admin') ?? false

  const [placaId, setPlacaId] = useState('')
  const [consultado, setConsultado] = useState(false)
  const [ultimoRegistro, setUltimoRegistro] = useState<PortatilRegistro | null>(null)
  const [registroAbierto, setRegistroAbierto] = useState<PortatilActivo | null>(null)
  const [mostrarCierre, setMostrarCierre] = useState(false)

  const buscar = useBuscarPortatil()
  const portatil = buscar.data

  let status: ScannerStatus = 'idle'
  let message: string | null = null
  let procesable = false

  if (buscar.isPending) {
    status = 'loading'
    message = 'Consultando inventario…'
  } else if (consultado && buscar.isError) {
    status = 'error'
    message = buscar.error instanceof ApiError ? buscar.error.message : 'No se pudo consultar el inventario.'
  } else if (portatil) {
    const v = veredicto(portatil, esAdmin)
    status = v.status
    message = v.message
    procesable = v.procesable
  }

  const portatilListo = procesable && portatil ? portatil : null

  const handleSubmit = (valor: string) => {
    if (buscar.isPending) return
    setUltimoRegistro(null)
    setRegistroAbierto(null)
    setMostrarCierre(false)
    setPlacaId(valor)
    setConsultado(true)
    buscar.mutate(valor, {
      onSuccess: (data) => {
        if (data.portatil_activo && esRegistroCerrable(data.portatil_activo.estado)) {
          setRegistroAbierto(data.portatil_activo)
        }
      },
    })
  }

  const limpiar = () => {
    setPlacaId('')
    setConsultado(false)
    buscar.reset()
  }

  const handleGuardado = (registro: PortatilRegistro) => {
    setUltimoRegistro(registro)
    limpiar()
  }

  const handleCerrado = (registro: PortatilRegistro) => {
    setRegistroAbierto(null)
    setMostrarCierre(false)
    setUltimoRegistro(registro)
    limpiar()
  }

  return (
    <div className="flex flex-col gap-6 p-6">
      <header>
        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
          Portátiles y Garantías
        </h1>
        <p className="mt-1 text-sm text-slate-500">
          Escanee la placa para diagnosticar la laptop o cerrar su registro abierto.
        </p>
      </header>

      {ultimoRegistro && (
        <div className="mx-auto flex w-full max-w-2xl items-center justify-between rounded-md border border-estado-verde-line bg-estado-verde px-4 py-3">
          <p className="flex items-center gap-2 text-sm text-estado-verde-ink">
            Registro guardado — estado <StatusBadge estado={ultimoRegistro.estado} />
          </p>
          <button
            type="button"
            onClick={() => setUltimoRegistro(null)}
            className="text-xs font-medium text-estado-verde-ink underline-offset-2 hover:underline"
          >
            Nuevo registro
          </button>
        </div>
      )}

      <section className="mx-auto w-full max-w-2xl">
        <ScannerInput
          value={placaId}
          onChange={setPlacaId}
          onSubmit={handleSubmit}
          status={status}
          message={message}
        />
      </section>

      {portatil && <EquipoResumen equipo={portatil} />}

      {registroAbierto && portatil && (
        <div className="mx-auto flex w-full max-w-2xl flex-col gap-3 rounded-md border border-estado-ambar-line bg-estado-ambar px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p className="flex items-center gap-2 text-sm text-estado-ambar-ink">
              Registro único abierto
              <StatusBadge estado={registroAbierto.estado} />
            </p>
            <p className="mt-1 font-mono text-xs text-estado-ambar-ink">
              {portatil.placa_id} · {ESTADO_LABELS[registroAbierto.estado]}
            </p>
          </div>
          <button
            type="button"
            onClick={() => setMostrarCierre(true)}
            className="rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800"
          >
            Cerrar Registro / Enviar a Almacén
          </button>
        </div>
      )}

      {portatilListo && (
        <PortatilForm key={portatilListo.placa_id} portatil={portatilListo} onGuardado={handleGuardado} />
      )}

      {mostrarCierre && registroAbierto && portatil && (
        <CierrePortatilModal
          registro={registroAbierto}
          placaId={portatil.placa_id}
          onCerrado={handleCerrado}
          onCancelar={() => setMostrarCierre(false)}
        />
      )}
    </div>
  )
}
