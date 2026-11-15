import { createFileRoute } from '@tanstack/react-router'
import { ZonasUbicacionesScreen } from '@/features/zonas-ubicaciones/ZonasUbicacionesScreen'

export const Route = createFileRoute('/zonas-ubicaciones')({
  component: ZonasUbicacionesScreen,
})
