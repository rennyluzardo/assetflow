import { createFileRoute } from '@tanstack/react-router'
import { PortatilesScreen } from '@/features/portatiles/PortatilesScreen'

export const Route = createFileRoute('/portatiles')({
  component: PortatilesScreen,
})
