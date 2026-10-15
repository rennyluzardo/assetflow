import type { UbicacionDestino } from '@/constants/equipos'
import { UBICACION_LABELS } from '@/constants/equipos'
import { cn } from '@/lib/cn'

interface DestinoStepProps {
  opciones: UbicacionDestino[]
  seleccion: UbicacionDestino | null
  onSeleccionar: (destino: UbicacionDestino) => void
  deshabilitado?: boolean
}

/** Selector de `ubicacion_destino` filtrado únicamente a las opciones válidas. */
export function DestinoStep({ opciones, seleccion, onSeleccionar, deshabilitado }: DestinoStepProps) {
  return (
    <section>
      <h3 className="text-sm font-medium text-slate-700">2. Ubicación de destino</h3>
      <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
        {opciones.map((destino) => (
          <button
            key={destino}
            type="button"
            disabled={deshabilitado}
            onClick={() => onSeleccionar(destino)}
            className={cn(
              'rounded-md border px-4 py-3 text-left text-sm font-medium transition-colors',
              seleccion === destino
                ? 'border-slate-400 bg-muted text-slate-900'
                : 'border-line bg-surface text-slate-600 hover:border-slate-300 hover:bg-muted',
            )}
          >
            {UBICACION_LABELS[destino]}
          </button>
        ))}
      </div>
      {opciones.length === 1 && (
        <p className="mt-2 text-xs text-slate-500">Única opción permitida para esta ruta del flujo.</p>
      )}
    </section>
  )
}
