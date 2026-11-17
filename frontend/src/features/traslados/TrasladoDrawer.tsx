import { Drawer } from '@/components/Drawer'
import { useDetalleTraslado } from './api'
import { EstadoTrasladoBadge, formatearFecha, rutaTraslado } from './componentes'
import { PanelAbierto } from './PanelAbierto'
import { PanelRecibido } from './PanelRecibido'
import { PanelTransito } from './PanelTransito'
import type { Traslado } from './types'

interface TrasladoDrawerProps {
  trasladoId: number
  onCerrar: () => void
}

/** Responsable con etiqueta de una etapa del ciclo de vida. */
function Responsable({ etiqueta, nombre }: { etiqueta: string; nombre: string | undefined | null }) {
  return (
    <p className="text-xs text-slate-500">
      <span className="font-medium text-slate-600">{etiqueta}:</span>{' '}
      {nombre ?? '—'}
    </p>
  )
}

/** Cabecera del detalle: código, estado, ruta, responsables y observaciones. */
function Cabecera({ traslado }: { traslado: Traslado }) {
  return (
    <div className="space-y-3">
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="text-xs uppercase tracking-wide text-slate-400">Código de traslado</p>
          <p className="font-mono text-2xl font-semibold tracking-tight text-slate-900">
            {traslado.codigo}
          </p>
          <p className="mt-1 text-sm text-slate-600">{rutaTraslado(traslado)}</p>
        </div>
        <EstadoTrasladoBadge estado={traslado.estado} />
      </div>

      {traslado.observaciones && (
        <p className="rounded-md border border-line bg-canvas p-3 text-sm text-slate-600">
          {traslado.observaciones}
        </p>
      )}

      <div className="grid grid-cols-2 gap-x-4 gap-y-1 rounded-md border border-line bg-canvas p-3">
        <Responsable etiqueta="Creado por" nombre={traslado.creador?.name} />
        <Responsable etiqueta="Creado" nombre={formatearFecha(traslado.created_at)} />
        <Responsable etiqueta="Despachado por" nombre={traslado.despachador?.name} />
        <Responsable etiqueta="Despachado" nombre={formatearFecha(traslado.despachado_at)} />
        <Responsable etiqueta="Recibido por" nombre={traslado.receptor?.name} />
        <Responsable etiqueta="Recibido" nombre={formatearFecha(traslado.recibido_at)} />
      </div>
    </div>
  )
}

/**
 * Detalle lateral adaptativo: según el estado del traslado (ABIERTO,
 * EN_TRANSITO o RECIBIDO) monta el panel de trabajo correspondiente.
 */
export function TrasladoDrawer({ trasladoId, onCerrar }: TrasladoDrawerProps) {
  const detalle = useDetalleTraslado(trasladoId)
  const traslado = detalle.data?.traslado

  return (
    <Drawer titulo="Detalle de traslado" onCerrar={onCerrar}>
      {detalle.isLoading ? (
        <p className="py-4 text-sm text-slate-400">Cargando traslado…</p>
      ) : detalle.isError || !traslado ? (
        <p className="py-4 text-sm text-estado-terracota-ink">No se pudo cargar el traslado.</p>
      ) : (
        <div className="space-y-5 py-4">
          <Cabecera traslado={traslado} />

          {traslado.estado === 'ABIERTO' && (
            <PanelAbierto trasladoId={trasladoId} equipos={detalle.data?.equipos ?? []} />
          )}
          {traslado.estado === 'EN_TRANSITO' && (
            <PanelTransito trasladoId={trasladoId} equipos={detalle.data?.equipos ?? []} />
          )}
          {traslado.estado === 'RECIBIDO' && (
            <PanelRecibido equipos={detalle.data?.equipos ?? []} />
          )}
        </div>
      )}
    </Drawer>
  )
}
