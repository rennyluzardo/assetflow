import { createFileRoute } from '@tanstack/react-router'
import { InventarioScreen } from '@/features/inventario/InventarioScreen'

export const Route = createFileRoute('/inventario')({
  component: InventarioScreen,
})
