import type { TipoGestion } from '@/constants/equipos'
import { TIPO_GESTION_LABELS } from '@/constants/equipos'
import { cn } from '@/lib/cn'

const OPCIONES: TipoGestion[] = ['NOVEDAD', 'DIAGNOSTICO', 'INTERVENCION']

interface TipoGestionStepProps {
  seleccion: TipoGestion | null
  onSeleccionar: (tipo: TipoGestion) => void
  deshabilitado?: boolean
}

export function TipoGestionStep({ seleccion, onSeleccionar, deshabilitado }: TipoGestionStepProps) {
  return (
    <section>
      <h3 className="text-sm font-medium text-slate-700">1. Tipo de gestión</h3>
      <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
        {OPCIONES.map((tipo) => (
          <button
            key={tipo}
            type="button"
            disabled={deshabilitado}
            onClick={() => onSeleccionar(tipo)}
            className={cn(
              'rounded-md border px-4 py-3 text-sm font-medium transition-colors',
              seleccion === tipo
                ? 'border-slate-400 bg-muted text-slate-900'
                : 'border-line bg-surface text-slate-600 hover:border-slate-300 hover:bg-muted',
            )}
          >
            {TIPO_GESTION_LABELS[tipo]}
          </button>
        ))}
      </div>
    </section>
  )
}
