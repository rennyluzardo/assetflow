import type { ReactNode } from 'react'
import { cn } from '@/lib/cn'

interface Props {
  children: ReactNode
  className?: string
}

/**
 * Superficie ejecutiva compartida por las tarjetas del dashboard: borde slate
 * sutil, esquinas suaves y sombra mínima. El padding lo decide cada tarjeta.
 */
export function DashboardCard({ children, className }: Props) {
  return (
    <div className={cn('rounded-xl border border-slate-200 bg-surface shadow-sm', className)}>
      {children}
    </div>
  )
}
