import type { EventoTrazabilidad } from './types'
import { UBICACION_LABELS } from '@/constants/equipos'
import { StatusBadge } from '@/components/StatusBadge'

/** Fecha/hora legible en `font-mono` para la línea de tiempo. */
function formatearFecha(iso: string): string {
  const fecha = new Date(iso)
  if (Number.isNaN(fecha.getTime())) return iso
  return fecha.toLocaleString('es-CO', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

/** Línea de tiempo vertical escandinava de los eventos unificados del activo. */
export function TrazabilidadTimeline({ eventos }: { eventos: EventoTrazabilidad[] }) {
  if (eventos.length === 0) {
    return (
      <div className="mx-auto w-full max-w-2xl rounded-lg border border-dashed border-line bg-surface p-8 text-center text-sm text-slate-400">
        Sin eventos registrados para este activo.
      </div>
    )
  }

  return (
    <ol className="mx-auto w-full max-w-2xl">
      {eventos.map((evento, indice) => (
        <TimelineItem
          key={`${evento.tipo}-${evento.fecha}-${indice}`}
          evento={evento}
          esUltimo={indice === eventos.length - 1}
        />
      ))}
    </ol>
  )
}

function TimelineItem({ evento, esUltimo }: { evento: EventoTrazabilidad; esUltimo: boolean }) {
  return (
    <li className="relative flex gap-4 pb-6 last:pb-0">
      <div className="flex flex-col items-center">
        <span className="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full border-2 border-slate-300 bg-surface" />
        {!esUltimo && <span className="w-px flex-1 bg-line" />}
      </div>

      <div className="min-w-0 flex-1 rounded-lg border border-line bg-surface p-4">
        <div className="flex items-center justify-between gap-3">
          <span className="text-sm font-medium text-slate-800">{evento.estacion}</span>
          {evento.estado && <StatusBadge estado={evento.estado} />}
        </div>

        <p className="mt-1 font-mono text-xs text-slate-500">{formatearFecha(evento.fecha)}</p>

        <dl className="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
          <div>
            <dt className="text-xs text-slate-500">Analista</dt>
            <dd className="text-slate-700">{evento.analista ?? '—'}</dd>
          </div>
          <div>
            <dt className="text-xs text-slate-500">Ubicación asignada</dt>
            <dd className="text-slate-700">
              {evento.ubicacion ? UBICACION_LABELS[evento.ubicacion] : '—'}
            </dd>
          </div>
        </dl>

        {evento.detalle && <p className="mt-2 text-sm text-slate-600">{evento.detalle}</p>}

        {evento.foto && (
          <div className="mt-3 flex items-center gap-3">
            <img
              src={`/storage/${evento.foto}`}
              alt="Evidencia fotográfica"
              className="h-16 w-16 rounded-md border border-line object-cover"
              onError={(e) => {
                e.currentTarget.style.display = 'none'
              }}
            />
            <span className="break-all font-mono text-xs text-slate-400">{evento.foto}</span>
          </div>
        )}
      </div>
    </li>
  )
}
