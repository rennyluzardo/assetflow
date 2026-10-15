import { createFileRoute } from '@tanstack/react-router'
import { CargueMasivoScreen } from '@/features/cargue-masivo/CargueMasivoScreen'

export const Route = createFileRoute('/cargue-masivo')({
  component: CargueMasivoScreen,
})
