import { createFileRoute } from '@tanstack/react-router'
import { EquiposScreen } from '@/features/equipos/EquiposScreen'

export const Route = createFileRoute('/equipos')({
  component: EquiposScreen,
})
