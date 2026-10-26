export type PeriodoDashboard = 'hoy' | 'semana' | 'mes' | 'anio'
export type ModuloDashboard = 'EQUIPOS' | 'SOPLADO' | 'PORTATILES'

export interface DashboardKpis {
  total_inventario: number
  cerrados: number
  en_transito: number
  intervenciones_periodo: number
}

export interface ConteoEstado {
  estado: string
  total: number
}

export interface ProductividadAnalista {
  analista_id: number
  nombre: string
  total: number
  meta: number
}

export interface ProductividadEstacion {
  estacion: string
  label: string
  total: number
}

/** Respuesta de `GET /api/dashboard`. */
export interface DashboardResponse {
  periodo: PeriodoDashboard
  desde: string
  hasta: string
  dias: number
  meta_diaria: number
  solo_propio: boolean
  kpis: DashboardKpis
  conteo_por_estado: ConteoEstado[]
  productividad: {
    por_analista: ProductividadAnalista[]
    por_estacion: ProductividadEstacion[]
  }
}

/** Parámetros de consulta del dashboard. */
export interface DashboardParams {
  periodo?: PeriodoDashboard
  modulo?: ModuloDashboard
  estado?: string
}
