import { useState } from 'react'
import { Drawer } from '@/components/Drawer'
import { useEquiposTraslado, useRecibirTraslado } from './api'
import { EquiposLista, EstadoTrasladoBadge, mensajeError, puedeRecibir } from './componentes'
import { ZONA_LABELS } from './constantes'
import type { Traslado } from './types'

interface TrasladoDrawerProps {
  traslado: Traslado
  sede: string | null
  onCerrar: () => void
}

/**
 * Detalle lateral de un traslado: preserva el contexto del listado maestro y
 * muestra los equipos asociados (`useEquiposTraslado`) y, si corresponde, la
 * acción de recepción en la sede de destino.
 */
export function TrasladoDrawer({ traslado, sede, onCerrar }: TrasladoDrawerProps) {
  const [recibido, setRecibido] = useState(false)
  const equipos = useEquiposTraslado(traslado.codigo)
  const recibir = useRecibirTraslado()

  const estado: Traslado['estado'] = recibido ? 'RECIBIDO' : traslado.estado
  const mostrarRecibir = puedeRecibir(traslado, sede) && !recibido
  const ubicaciones = [traslado.ubicacion_origen_exacta, traslado.ubicacion_destino_exacta]
    .filter(Boolean)
    .join(' → ')

  const handleRecibir = () => {
    recibir.mutate(traslado.codigo, { onSuccess: () => setRecibido(true) })
  }

  return (
    <Drawer
      titulo={`Traslado ${traslado.codigo}`}
      onCerrar={onCerrar}
      footer={
        mostrarRecibir ? (
          <button
            type="button"
            onClick={handleRecibir}
            disabled={recibir.isPending}
            className="w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {recibir.isPending ? 'Recibiendo…' : 'Recibir Traslado'}
          </button>
        ) : undefined
      }
    >
      <div className="space-y-5">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <p className="text-sm text-slate-500">
              {ZONA_LABELS[traslado.zona_origen]} → {ZONA_LABELS[traslado.zona_destino]}
            </p>
            {ubicaciones && <p className="mt-0.5 text-xs text-slate-400">{ubicaciones}</p>}
          </div>
          <EstadoTrasladoBadge estado={estado} />
        </div>

        <div>
          <h3 className="mb-2 text-sm font-semibold text-slate-700">Lista de equipos del traslado</h3>
          {equipos.isLoading ? (
            <p className="text-sm text-slate-400">Cargando equipos…</p>
          ) : equipos.isError ? (
            <p className="text-sm text-estado-terracota-ink">No se pudieron cargar los equipos.</p>
          ) : (
            <EquiposLista equipos={equipos.data?.equipos ?? []} />
          )}
        </div>

        {recibir.isError && (
          <div className="rounded-md border border-estado-terracota-line bg-estado-terracota p-3">
            <p className="text-sm font-medium text-estado-terracota-ink">Recepción bloqueada</p>
            <p className="mt-1 text-sm text-estado-terracota-ink/90">{mensajeError(recibir.error)}</p>
          </div>
        )}

        {recibido && (
          <div className="rounded-md border border-estado-verde-line bg-estado-verde p-3">
            <p className="text-sm font-medium text-estado-verde-ink">
              Traslado {traslado.codigo} recibido correctamente.
            </p>
          </div>
        )}
      </div>
    </Drawer>
  )
}
