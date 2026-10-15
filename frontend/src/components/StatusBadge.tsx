import type { EstadoEquipo } from '@/constants/equipos'
import { ESTADO_LABELS, ESTADO_SEMANTICA, SEMANTICA_CLASSES } from '@/constants/equipos'
import { cn } from '@/lib/cn'

interface StatusBadgeProps {
  estado: EstadoEquipo
  className?: string
}

/** Píldora semántica que visualiza un estado de la máquina de estados. */
export function StatusBadge({ estado, className }: StatusBadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
        SEMANTICA_CLASSES[ESTADO_SEMANTICA[estado]],
        className,
      )}
    >
      {ESTADO_LABELS[estado]}
    </span>
  )
}
