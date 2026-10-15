import { createFileRoute } from '@tanstack/react-router'
import { SopladoScreen } from '@/features/soplado/SopladoScreen'

export const Route = createFileRoute('/soplado')({
  component: SopladoScreen,
})
