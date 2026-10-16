import { createFileRoute } from '@tanstack/react-router'
import { TrazabilidadScreen } from '@/features/trazabilidad/TrazabilidadScreen'

export const Route = createFileRoute('/trazabilidad')({
  validateSearch: (search: Record<string, unknown>): { placa_id?: string } => ({
    placa_id: typeof search.placa_id === 'string' ? search.placa_id : undefined,
  }),
  component: TrazabilidadScreen,
})
