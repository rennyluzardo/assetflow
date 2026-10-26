import { useEffect, useState } from 'react'
import { useSearch } from '@tanstack/react-router'
import { ApiError } from '@/lib/api'
import { ScannerInput } from '@/components/ScannerInput'
import type { ScannerStatus } from '@/components/ScannerInput'
import { StatusBadge } from '@/components/StatusBadge'
import { UBICACION_LABELS } from '@/constants/equipos'
import { cn } from '@/lib/cn'
import { useBuscarTrazabilidad } from './api'
import { TrazabilidadTimeline } from './TrazabilidadTimeline'
import type { ActivoTrazabilidad } from './types'

/** Ficha maestra del activo (Identificador 1 / 2 y estado actual). */
function ActivoResumen({ activo }: { activo: ActivoTrazabilidad }) {
  return (
    <dl className="mx-auto grid w-full max-w-2xl grid-cols-2 gap-x-6 gap-y-4 rounded-lg border border-line bg-surface p-5 sm:grid-cols-3">
      <Campo etiqueta="Placa" mono>{activo.placa_id}</Campo>
      <Campo etiqueta="Serial" mono>{activo.serial}</Campo>
      <Campo etiqueta="N.º Traslado" mono>{activo.num_traslado}</Campo>
      <Campo etiqueta="Marca">{activo.marca ?? '—'}</Campo>
      <Campo etiqueta="Referencia">{activo.referencia ?? '—'}</Campo>
      <Campo etiqueta="Descripción">{activo.descripcion}</Campo>
      <Campo etiqueta="Ubicación actual">
        {activo.ubicacion_actual ? UBICACION_LABELS[activo.ubicacion_actual] : '—'}
      </Campo>
      {activo.estado_actual && (
        <div>
          <dt className="text-xs text-slate-500">Estado actual</dt>
          <dd className="mt-1">
            <StatusBadge estado={activo.estado_actual} />
          </dd>
        </div>
      )}
      <Campo etiqueta="Intervenido">{activo.intervenido ? 'Sí' : 'No'}</Campo>
    </dl>
  )
}

function Campo({ etiqueta, mono, children }: { etiqueta: string; mono?: boolean; children: string }) {
  return (
    <div>
      <dt className="text-xs text-slate-500">{etiqueta}</dt>
      <dd className={cn('mt-0.5 break-all text-sm text-slate-900', mono && 'font-mono')}>{children}</dd>
    </div>
  )
}

/** Hoja de vida del activo: ficha maestra + línea de tiempo cronológica. */
export function TrazabilidadScreen() {
  const { placa_id } = useSearch({ from: '/trazabilidad' })
  const [consulta, setConsulta] = useState('')
  const [consultado, setConsultado] = useState(false)

  const buscar = useBuscarTrazabilidad()
  const data = buscar.data

  useEffect(() => {
    if (!placa_id) return
    setConsulta(placa_id)
    setConsultado(true)
    buscar.mutate(placa_id)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [placa_id])

  let status: ScannerStatus = 'idle'
  let message: string | null = null

  if (buscar.isPending) {
    status = 'loading'
    message = 'Buscando hoja de vida…'
  } else if (consultado && buscar.isError) {
    status = 'error'
    message = buscar.error instanceof ApiError ? buscar.error.message : 'No se pudo consultar la trazabilidad.'
  } else if (data) {
    status = 'success'
    message = 'Activo encontrado.'
  }

  const handleSubmit = (valor: string) => {
    if (buscar.isPending) return
    setConsulta(valor)
    setConsultado(true)
    buscar.mutate(valor)
  }

  return (
    <div className="flex flex-col gap-6 p-6">
      <header>
        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Trazabilidad</h1>
        <p className="mt-1 text-sm text-slate-500">
          Hoja de vida y línea de tiempo del activo (solo lectura).
        </p>
      </header>

      <section className="mx-auto w-full max-w-2xl">
        <ScannerInput
          value={consulta}
          onChange={setConsulta}
          onSubmit={handleSubmit}
          status={status}
          message={message}
          label="Placa o Serial"
          placeholder="Escanea o escribe placa o serial y presiona Enter"
        />
      </section>

      {data && <ActivoResumen activo={data.activo} />}

      {data && <TrazabilidadTimeline eventos={data.eventos} />}
    </div>
  )
}
