import { useState } from 'react'
import type { EquipoRegistro, InventarioBusqueda } from '@/types/equipos'
import { ESTADO_LABELS } from '@/constants/equipos'
import { ApiError } from '@/lib/api'
import { ScannerInput } from '@/components/ScannerInput'
import type { ScannerStatus } from '@/components/ScannerInput'
import { StatusBadge } from '@/components/StatusBadge'
import { useBuscarEquipo } from './api'
import { EquipoResumen } from './EquipoResumen'
import { EquipoForm } from './EquipoForm'

/**
 * Clasifica el resultado del escaneo en la semántica visual nórdica:
 *  - verde  → disponible para procesar (sin flujo abierto);
 *  - ámbar  → estado intermedio/abierto (reanudable aquí, o en tránsito a /soplado);
 *  - terracota → bloqueo (ya cerrado / no requiere pasar por esta estación).
 */
function veredicto(equipo: InventarioBusqueda): {
  status: ScannerStatus
  message: string
  procesable: boolean
} {
  const activo = equipo.equipo_activo
  if (!activo) {
    return { status: 'success', message: 'Equipo válido — listo para procesar.', procesable: true }
  }

  switch (activo.estado) {
    case 'PENDIENTE_GARANTIA':
    case 'POR_REACONDICIONAR':
      return {
        status: 'warning',
        message: 'Equipo con flujo abierto — completar cierre.',
        procesable: true,
      }
    case 'POR_SOPLAR':
      return {
        status: 'warning',
        message: 'Equipo en cola de soplado — continúe en /soplado.',
        procesable: false,
      }
    default:
      return {
        status: 'error',
        message: `Equipo ya intervenido (${ESTADO_LABELS[activo.estado]}).`,
        procesable: false,
      }
  }
}

export function EquiposScreen() {
  const [placaId, setPlacaId] = useState('')
  const [consultado, setConsultado] = useState(false)
  const [ultimoRegistro, setUltimoRegistro] = useState<EquipoRegistro | null>(null)

  const buscar = useBuscarEquipo()
  const equipo = buscar.data

  let status: ScannerStatus = 'idle'
  let message: string | null = null
  let procesable = false

  if (buscar.isPending) {
    status = 'loading'
    message = 'Consultando inventario…'
  } else if (consultado && buscar.isError) {
    status = 'error'
    message = buscar.error instanceof ApiError ? buscar.error.message : 'No se pudo consultar el inventario.'
  } else if (equipo) {
    const v = veredicto(equipo)
    status = v.status
    message = v.message
    procesable = v.procesable
  }

  const equipoListo = procesable && equipo ? equipo : null

  const handleSubmit = (valor: string) => {
    if (buscar.isPending) return
    setUltimoRegistro(null)
    setPlacaId(valor)
    setConsultado(true)
    buscar.mutate(valor)
  }

  const handleGuardado = (registro: EquipoRegistro) => {
    setUltimoRegistro(registro)
    setPlacaId('')
    setConsultado(false)
    buscar.reset()
  }

  return (
    <div className="flex flex-col gap-6 p-6">
      <header>
        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
          Diagnóstico e Intervención de CPUs
        </h1>
        <p className="mt-1 text-sm text-slate-500">
          Escanee la placa con la pistola para iniciar el registro clínico.
        </p>
      </header>

      {ultimoRegistro && (
        <div className="flex max-w-2xl items-center justify-between rounded-md border border-estado-verde-line bg-estado-verde px-4 py-3">
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

      <section className="max-w-2xl">
        <ScannerInput
          value={placaId}
          onChange={setPlacaId}
          onSubmit={handleSubmit}
          status={status}
          message={message}
        />
      </section>

      {equipo && <EquipoResumen equipo={equipo} />}

      {equipoListo && (
        <EquipoForm key={equipoListo.placa_id} equipo={equipoListo} onGuardado={handleGuardado} />
      )}
    </div>
  )
}
