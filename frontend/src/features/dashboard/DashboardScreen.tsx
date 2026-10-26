import { useState } from 'react'
import { useAuth } from '@/features/auth/AuthContext'
import {
  ESTADO_LABELS,
  ESTADO_SEMANTICA,
  SEMANTICA_CLASSES,
} from '@/constants/equipos'
import type { EstadoEquipo, EstadoSemantica } from '@/constants/equipos'
import { cn } from '@/lib/cn'
import { descargarBitacoraCsv, useDashboard } from './api'
import type { ModuloDashboard, PeriodoDashboard } from './types'

const PERIODOS: ReadonlyArray<{ value: PeriodoDashboard; label: string }> = [
  { value: 'hoy', label: 'Hoy' },
  { value: 'semana', label: 'Semana' },
  { value: 'mes', label: 'Mes' },
  { value: 'anio', label: 'Año' },
]

const ESTACIONES: ReadonlyArray<{ value: ModuloDashboard | ''; label: string }> = [
  { value: '', label: 'Todas las estaciones' },
  { value: 'EQUIPOS', label: 'Diagnóstico CPU' },
  { value: 'SOPLADO', label: 'Soplado' },
  { value: 'PORTATILES', label: 'Portátiles' },
]

const PERIODO_LABEL: Record<PeriodoDashboard, string> = {
  hoy: 'hoy',
  semana: 'esta semana',
  mes: 'este mes',
  anio: 'este año',
}

function semanticaEstado(estado: string): { label: string; className: string } {
  const clave = estado as EstadoEquipo
  const semantica: EstadoSemantica = ESTADO_SEMANTICA[clave] ?? 'ambar'
  return {
    label: ESTADO_LABELS[clave] ?? estado,
    className: SEMANTICA_CLASSES[semantica],
  }
}

function KpiCard({
  titulo,
  valor,
  sub,
  tono = 'slate',
}: {
  titulo: string
  valor: string | number
  sub?: string
  tono?: 'slate' | 'verde' | 'ambar'
}) {
  const valorTono = {
    slate: 'text-slate-900',
    verde: 'text-estado-verde-ink',
    ambar: 'text-estado-ambar-ink',
  }[tono]

  return (
    <div className="rounded-lg border border-line bg-surface p-5">
      <p className="text-xs font-medium uppercase tracking-wide text-slate-400">{titulo}</p>
      <p className={cn('mt-2 font-mono text-3xl font-semibold tabular-nums', valorTono)}>
        {valor}
      </p>
      {sub && <p className="mt-1 text-xs text-slate-500">{sub}</p>}
    </div>
  )
}

/** Panel de distribución por estado de la máquina de estados (tríada semántica). */
function DistribucionEstados({ conteo }: { conteo: ReadonlyArray<{ estado: string; total: number }> }) {
  const total = conteo.reduce((suma, e) => suma + e.total, 0)

  if (total === 0) {
    return (
      <p className="text-sm text-slate-400">Sin estados registrados en el periodo seleccionado.</p>
    )
  }

  return (
    <div className="space-y-3">
      <div className="flex h-3 w-full overflow-hidden rounded-full bg-muted">
        {conteo.map((e) => {
          const { className } = semanticaEstado(e.estado)
          const ancho = `${(e.total / total) * 100}%`
          return <div key={e.estado} className={cn('h-full', className)} style={{ width: ancho }} />
        })}
      </div>
      <ul className="space-y-1.5">
        {conteo.map((e) => {
          const { label, className } = semanticaEstado(e.estado)
          return (
            <li key={e.estado} className="flex items-center justify-between text-sm">
              <span className="flex items-center gap-2 text-slate-600">
                <span className={cn('inline-block h-2.5 w-2.5 rounded-full border', className)} />
                {label}
              </span>
              <span className="font-mono text-slate-900">{e.total}</span>
            </li>
          )
        })}
      </ul>
    </div>
  )
}

function ProductividadAnalista({
  analistas,
}: {
  analistas: ReadonlyArray<{ nombre: string; total: number; meta: number }>
}) {
  if (analistas.length === 0) {
    return <p className="text-sm text-slate-400">Sin productividad registrada en el periodo.</p>
  }

  return (
    <ul className="space-y-4">
      {analistas.map((a) => {
        const pct = a.meta > 0 ? Math.min(100, Math.round((a.total / a.meta) * 100)) : 0
        return (
          <li key={a.nombre}>
            <div className="mb-1.5 flex items-baseline justify-between text-sm">
              <span className="font-medium text-slate-700">{a.nombre}</span>
              <span className="font-mono text-xs text-slate-500">
                {a.total} / {a.meta}
              </span>
            </div>
            <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
              <div
                className={cn(
                  'h-full rounded-full',
                  pct >= 100 ? 'bg-estado-verde-line' : 'bg-slate-400',
                )}
                style={{ width: `${pct}%` }}
              />
            </div>
          </li>
        )
      })}
    </ul>
  )
}

/** Panel de control operativo: KPIs, distribución por estado y productividad. */
export function DashboardScreen() {
  const { session, hasPermission } = useAuth()
  const [periodo, setPeriodo] = useState<PeriodoDashboard>('hoy')
  const [modulo, setModulo] = useState<ModuloDashboard | ''>('')
  const [exportando, setExportando] = useState(false)
  const [errorExportar, setErrorExportar] = useState<string | null>(null)

  const params = { periodo, ...(modulo ? { modulo } : {}) }
  const { data, isLoading, isError } = useDashboard(params)

  const esAdmin = session?.roles.includes('admin') ?? false
  const soloPropio = data?.solo_propio ?? (hasPermission('dashboard.ver_solo_propio') && !esAdmin)

  const exportar = async () => {
    setExportando(true)
    setErrorExportar(null)
    try {
      await descargarBitacoraCsv(params)
    } catch (err) {
      setErrorExportar(err instanceof Error ? err.message : 'No se pudo exportar la bitácora.')
    } finally {
      setExportando(false)
    }
  }

  return (
    <div className="flex flex-col gap-6 p-6">
      <header className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
            Dashboard de Control Operativo
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            Productividad del taller {esAdmin ? '(vista global)' : '(vista personal)'}.
          </p>
        </div>
        {data && (
          <p className="text-sm text-slate-500">
            Total inventario: <span className="font-mono">{data.kpis.total_inventario}</span>
          </p>
        )}
      </header>

      {soloPropio && (
        <div className="inline-flex w-fit items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
          Vista personal de productividad
        </div>
      )}

      <section className="mx-auto flex w-full max-w-7xl flex-wrap items-center gap-3 rounded-lg border border-line bg-surface p-3">
        <div className="flex rounded-md border border-line p-0.5">
          {PERIODOS.map((p) => (
            <button
              key={p.value}
              type="button"
              onClick={() => setPeriodo(p.value)}
              className={cn(
                'rounded px-3 py-1.5 text-sm font-medium transition-colors',
                periodo === p.value ? 'bg-slate-900 text-white' : 'text-slate-600 hover:text-slate-900',
              )}
            >
              {p.label}
            </button>
          ))}
        </div>

        <select
          aria-label="Filtro de estación"
          value={modulo}
          onChange={(event) => setModulo(event.target.value as ModuloDashboard | '')}
          className="rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-700 outline-none transition-colors focus:border-slate-400"
        >
          {ESTACIONES.map((e) => (
            <option key={e.value} value={e.value}>
              {e.label}
            </option>
          ))}
        </select>

        <button
          type="button"
          disabled={exportando}
          onClick={() => void exportar()}
          className="ml-auto rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
        >
          {exportando ? 'Exportando…' : 'Exportar Bitácora (.CSV)'}
        </button>
      </section>

      {errorExportar && (
        <p className="text-sm text-estado-terracota-ink">{errorExportar}</p>
      )}

      {isError && (
        <div className="mx-auto w-full max-w-7xl rounded-lg border border-line bg-surface p-6 text-center text-sm text-slate-500">
          No se pudieron cargar las métricas del dashboard.
        </div>
      )}

      {isLoading && !data && (
        <div className="mx-auto w-full max-w-7xl rounded-lg border border-line bg-surface p-6 text-center text-sm text-slate-400">
          Cargando métricas…
        </div>
      )}

      {data && (
        <>
          <section className="mx-auto grid w-full max-w-7xl grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <KpiCard
              titulo="Total procesados"
              valor={data.kpis.intervenciones_periodo}
              sub={`intervenciones ${PERIODO_LABEL[data.periodo]}`}
            />
            <KpiCard titulo="Cerrados" valor={data.kpis.cerrados} sub="flujos cerrados" tono="verde" />
            <KpiCard
              titulo="En tránsito"
              valor={data.kpis.en_transito}
              sub="cuellos de botella"
              tono="ambar"
            />
            <KpiCard
              titulo="Meta diaria"
              valor={data.meta_diaria}
              sub="intervenciones por técnico/día"
            />
          </section>

          <section className="mx-auto grid w-full max-w-7xl grid-cols-1 gap-6 lg:grid-cols-2">
            <div className="rounded-lg border border-line bg-surface p-5">
              <h2 className="mb-4 text-sm font-semibold text-slate-700">
                Distribución por estado
              </h2>
              <DistribucionEstados conteo={data.conteo_por_estado} />
            </div>

            <div className="rounded-lg border border-line bg-surface p-5">
              <h2 className="mb-4 text-sm font-semibold text-slate-700">
                Productividad por estación
              </h2>
              {data.productividad.por_estacion.length === 0 ? (
                <p className="text-sm text-slate-400">Sin intervenciones en el periodo.</p>
              ) : (
                <ul className="space-y-3">
                  {data.productividad.por_estacion.map((e) => (
                    <li key={e.estacion} className="flex items-center justify-between text-sm">
                      <span className="text-slate-600">{e.label}</span>
                      <span className="font-mono text-slate-900">{e.total}</span>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </section>

          <section className="mx-auto w-full max-w-7xl rounded-lg border border-line bg-surface p-5">
            <h2 className="mb-4 text-sm font-semibold text-slate-700">
              Productividad por analista{' '}
              <span className="font-normal text-slate-400">
                (meta: {data.meta_diaria}/día)
              </span>
            </h2>
            <ProductividadAnalista analistas={data.productividad.por_analista} />
          </section>
        </>
      )}
    </div>
  )
}
