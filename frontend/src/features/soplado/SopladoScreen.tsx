import { useState } from 'react'
import type { InventarioBusqueda } from '@/types/equipos'
import { ESTADO_LABELS } from '@/constants/equipos'
import { ApiError } from '@/lib/api'
import { ScannerInput } from '@/components/ScannerInput'
import type { ScannerStatus } from '@/components/ScannerInput'
import { StatusBadge } from '@/components/StatusBadge'
import { useBuscarSoplado } from './api'
import { SopladoResumen } from './SopladoResumen'
import { SopladoForm } from './SopladoForm'

type EstadoFinal = 'COMPLETADO' | 'INTERVENIDO'

/**
 * Veredicto del escaneo para la estación de soplado:
 *  - verde  → registro activo en POR_SOPLAR (procesable);
 *  - ámbar  → flujo abierto que aún no llega a soplado;
 *  - terracota → ya intervenido / sin gestión activa (bloqueado).
 */
function veredicto(equipo: InventarioBusqueda): {
  status: ScannerStatus
  message: string
  procesable: boolean
} {
  const activo = equipo.equipo_activo

  if (activo?.estado === 'POR_SOPLAR') {
    return {
      status: 'success',
      message: 'Equipo en cola de soplado — listo para despachar.',
      procesable: true,
    }
  }

  if (activo) {
    if (activo.estado === 'PENDIENTE_GARANTIA' || activo.estado === 'POR_REACONDICIONAR') {
      return {
        status: 'warning',
        message: 'Equipo con flujo abierto — aún no está en cola de soplado.',
        procesable: false,
      }
    }
    return {
      status: 'error',
      message: `Equipo ya intervenido (${ESTADO_LABELS[activo.estado]}).`,
      procesable: false,
    }
  }

  return {
    status: 'warning',
    message: 'Equipo sin gestión activa — no requiere soplado.',
    procesable: false,
  }
}

export function SopladoScreen() {
  const [placaId, setPlacaId] = useState('')
  const [consultado, setConsultado] = useState(false)
  const [ultimoEstado, setUltimoEstado] = useState<EstadoFinal | null>(null)

  const buscar = useBuscarSoplado()
  const equipo = buscar.data

  let status: ScannerStatus = 'idle'
  let message: string | null = null
  let procesable = false

  if (buscar.isPending) {
    status = 'loading'
    message = 'Consultando cola de soplado…'
  } else if (consultado && buscar.isError) {
    status = 'error'
    message = buscar.error instanceof ApiError ? buscar.error.message : 'No se pudo consultar la cola de soplado.'
  } else if (equipo) {
    const v = veredicto(equipo)
    status = v.status
    message = v.message
    procesable = v.procesable
  }

  const equipoListo = procesable && equipo ? equipo : null

  const handleSubmit = (valor: string) => {
    if (buscar.isPending) return
    setUltimoEstado(null)
    setPlacaId(valor)
    setConsultado(true)
    buscar.mutate(valor)
  }

  const handleGuardado = (estado: EstadoFinal) => {
    setUltimoEstado(estado)
    setPlacaId('')
    setConsultado(false)
    buscar.reset()
  }

  return (
    <div className="flex flex-col gap-6 p-6">
      <header>
        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
          Mantenimiento Preventivo / Soplado
        </h1>
        <p className="mt-1 text-sm text-slate-500">
          Escanee la placa para registrar la limpieza y el despacho final.
        </p>
      </header>

      {ultimoEstado && (
        <div className="flex max-w-2xl items-center justify-between rounded-md border border-estado-verde-line bg-estado-verde px-4 py-3">
          <p className="flex items-center gap-2 text-sm text-estado-verde-ink">
            Soplado registrado — estado <StatusBadge estado={ultimoEstado} />
          </p>
          <button
            type="button"
            onClick={() => setUltimoEstado(null)}
            className="text-xs font-medium text-estado-verde-ink underline-offset-2 hover:underline"
          >
            Nuevo registro
          </button>
        </div>
      )}

      <section className="max-w-2xl">
        <ScannerInput
          value={placaId}
          onChange={setPlacaId}
          onSubmit={handleSubmit}
          status={status}
          message={message}
          label="Placa (Identificador 1)"
        />
      </section>

      {equipo && <SopladoResumen equipo={equipo} />}

      {equipoListo && (
        <SopladoForm key={equipoListo.placa_id} equipo={equipoListo} onGuardado={handleGuardado} />
      )}
    </div>
  )
}
