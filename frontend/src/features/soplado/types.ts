import type { UbicacionDestino } from '@/constants/equipos'

export type TipoSuciedad = 'polvo' | 'cucarachas' | 'polvo_cucarachas' | 'ninguna'

export const TIPO_SUCIEDAD_OPTIONS: ReadonlyArray<{ value: TipoSuciedad; label: string }> = [
  { value: 'polvo', label: 'Polvo' },
  { value: 'cucarachas', label: 'Cucarachas' },
  { value: 'polvo_cucarachas', label: 'Polvo y Cucarachas' },
  { value: 'ninguna', label: 'Ninguna' },
]

/** Cuerpo multipart de `POST /api/soplado`. */
export interface RegistrarSopladoPayload {
  placa_id: string
  energiza: boolean
  da_video: boolean
  acceso_bios: boolean
  tipo_suciedad: TipoSuciedad
  aplica_pasta_termica: boolean
  foto_ruta: File
  ubicacion_destino: UbicacionDestino
}

/** Registro `soplado_registros` devuelto por el backend tras guardar. */
export interface SopladoRegistroResult {
  id: number
  equipo_id: number
  foto_ruta: string
  ubicacion_destino: UbicacionDestino
  analista_id: number
}
