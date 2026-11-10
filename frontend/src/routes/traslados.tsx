import { createFileRoute } from '@tanstack/react-router'
import { TrasladosScreen } from '@/features/traslados/TrasladosScreen'

export const Route = createFileRoute('/traslados')({
  component: TrasladosScreen,
})
