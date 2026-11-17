import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  LabelList,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { DashboardCard } from './DashboardCard'
import type { ProductividadEstacion } from '../types'

interface Props {
  estaciones: ReadonlyArray<ProductividadEstacion>
}

interface EstacionChartRow {
  clave: string
  estacion: string
  total: number
  color: string
}

/** Tonos que ya usa la dona de estados, para que ambos gráficos armonicen. */
const COLORES_ESTACION: Record<string, string> = {
  EQUIPOS: '#0ea5e9', // sky-500
  SOPLADO: '#10b981', // emerald-500
  PORTATILES: '#6366f1', // indigo-500
}

const COLOR_FALLBACK = '#94a3b8' // slate-400

/** Barras de intervenciones por estación de trabajo. */
export function ProductividadEstacionChart({ estaciones }: Props) {
  const chartData: EstacionChartRow[] = estaciones
    .filter((e) => e.total > 0)
    .map((e) => ({
      clave: e.estacion,
      estacion: e.label,
      total: e.total,
      color: COLORES_ESTACION[e.estacion] ?? COLOR_FALLBACK,
    }))

  if (chartData.length === 0) {
    return (
      <DashboardCard className="flex flex-col p-5">
        <h2 className="text-sm font-semibold text-slate-700">Productividad por estación</h2>
        <p className="mt-4 text-sm text-slate-400">Sin intervenciones en el periodo.</p>
      </DashboardCard>
    )
  }

  const totalPeriodo = chartData.reduce((suma, item) => suma + item.total, 0)

  return (
    <DashboardCard className="flex flex-col p-5">
      <div className="flex flex-wrap items-baseline justify-between gap-2">
        <div>
          <h2 className="text-sm font-semibold text-slate-700">Productividad por estación</h2>
          <p className="mt-1 text-xs text-slate-500">
            Intervenciones registradas en cada puesto de trabajo.
          </p>
        </div>
        <span className="font-mono text-xs text-slate-400">{totalPeriodo} intervenciones</span>
      </div>

      <div
        aria-label="Gráfico de barras con las intervenciones por estación de trabajo"
        className="mt-2 w-full"
        role="img"
      >
        <ResponsiveContainer height={250} width="100%">
          <BarChart data={chartData} margin={{ top: 24, right: 8, bottom: 0, left: -12 }}>
            <CartesianGrid stroke="#e2e8f0" vertical={false} />
            <XAxis
              axisLine={{ stroke: '#e2e8f0' }}
              dataKey="estacion"
              dy={6}
              interval={0}
              tick={{ fill: '#64748b', fontSize: 11 }}
              tickLine={false}
            />
            <YAxis
              allowDecimals={false}
              axisLine={false}
              tick={{ fill: '#64748b', fontSize: 11 }}
              tickLine={false}
              width={36}
            />
            <Tooltip
              cursor={{ fill: '#f1f5f9' }}
              content={({ active, payload }) => {
                if (!active || !payload?.length) return null
                const row = payload[0]?.payload as EstacionChartRow | undefined
                if (!row) return null
                const porcentaje =
                  totalPeriodo > 0 ? Math.round((row.total / totalPeriodo) * 100) : 0
                return (
                  <div className="rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur-sm">
                    <div className="mb-1 flex items-center gap-2">
                      <span
                        className="h-2.5 w-2.5 rounded-full"
                        style={{ backgroundColor: row.color }}
                      />
                      <p className="text-xs font-semibold text-slate-700">{row.estacion}</p>
                    </div>
                    <p className="text-lg font-bold text-slate-900">
                      {row.total}{' '}
                      <span className="text-xs font-medium text-slate-500">intervenciones</span>
                    </p>
                    <p className="mt-1 font-mono text-[11px] text-slate-500">
                      {porcentaje}% del total del periodo
                    </p>
                  </div>
                )
              }}
            />
            <Bar dataKey="total" maxBarSize={56} radius={[4, 4, 0, 0]}>
              {chartData.map((entry) => (
                <Cell fill={entry.color} key={entry.clave} />
              ))}
              <LabelList dataKey="total" fill="#334155" fontSize={11} position="top" />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>

      <table className="sr-only">
        <caption>Intervenciones por estación de trabajo en el periodo</caption>
        <thead>
          <tr>
            <th scope="col">Estación</th>
            <th scope="col">Intervenciones</th>
          </tr>
        </thead>
        <tbody>
          {chartData.map((row) => (
            <tr key={row.clave}>
              <th scope="row">{row.estacion}</th>
              <td>{row.total}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </DashboardCard>
  )
}
