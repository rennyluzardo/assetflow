export type PeriodoDashboard = 'hoy' | 'semana' | 'mes' | 'anio'
export type ModuloDashboard = 'EQUIPOS' | 'SOPLADO' | 'PORTATILES'
export type TipoEquipoDashboard = 'CPU' | 'PORTATIL'

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

/** Resumen de cumplimiento del SLA (fecha_cargue -> fecha_intervencion <= 4 días). */
export interface SlaResumen {
  dentro: number
  total: number
  porcentaje: number
}

export interface ProductividadAnalista {
  analista_id: number
  nombre: string
  total: number
  meta: number
  porcentaje: number
  bono: number
  sla: SlaResumen
}

export interface ProductividadGlobal {
  total: number
  meta: number
  porcentaje: number
  bono: number
  sla: SlaResumen
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
  sla_dias: number
  solo_propio: boolean
  kpis: DashboardKpis
  conteo_por_estado: ConteoEstado[]
  productividad: {
    global: ProductividadGlobal
    por_analista: ProductividadAnalista[]
    por_estacion: ProductividadEstacion[]
  }
}

/** Parámetros de consulta del dashboard. */
export interface DashboardParams {
  periodo?: PeriodoDashboard
  modulo?: ModuloDashboard
  tipo_equipo?: TipoEquipoDashboard
  estado?: string
  fecha_inicio?: string
  fecha_fin?: string
}
