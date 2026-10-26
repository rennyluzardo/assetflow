import { StatusBadge } from '@/components/StatusBadge'
import type { EstadoEquipo } from '@/constants/equipos'

/** Estado semántico o píldora neutra cuando el activo aún no ha sido intervenido. */
export function CeldaEstado({ estado }: { estado: EstadoEquipo | null }) {
  if (estado === null) {
    return (
      <span className="inline-flex items-center rounded-full border border-line bg-muted px-2.5 py-0.5 text-xs font-medium text-slate-500">
        Sin estado
      </span>
    )
  }

  return <StatusBadge estado={estado} />
}
