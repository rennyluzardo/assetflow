import { createFileRoute } from '@tanstack/react-router'
import { TrazabilidadScreen } from '@/features/trazabilidad/TrazabilidadScreen'

export const Route = createFileRoute('/trazabilidad')({
  component: TrazabilidadScreen,
})
