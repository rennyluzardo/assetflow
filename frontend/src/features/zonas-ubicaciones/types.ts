export interface Ubicacion {
  id: number
  zona_id: number
  nombre: string
  activo: boolean
}

export interface Zona {
  id: number
  sede: string
  nombre: string
  activo: boolean
  ubicaciones: Ubicacion[]
}
