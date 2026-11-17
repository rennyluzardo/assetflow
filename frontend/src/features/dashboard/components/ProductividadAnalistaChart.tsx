import {
  Bar,
  BarChart,
  CartesianGrid,
  LabelList,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { DashboardCard } from './DashboardCard'
import type { ProductividadAnalista, SlaResumen } from '../types'

interface Props {
  analistas: ReadonlyArray<ProductividadAnalista>
  metaDiaria?: number
}

interface AnalistaChartRow {
  analista_id: number
  nombre_analista: string
  intervenciones: number
  meta: number
  porcentaje: number
  bono: number
  sla: SlaResumen
}

const COLOR_BARRA = '#10b981' // emerald-500, mismo tono que «Completado» en la dona

function truncarNombre(nombre: string): string {
  return nombre.length > 16 ? `${nombre.slice(0, 15)}…` : nombre
}

function Encabezado({ metaDiaria }: { metaDiaria?: number }) {
  return (
    <div className="flex flex-wrap items-baseline justify-between gap-2">
      <div>
        <h2 className="text-sm font-semibold text-slate-700">Productividad por analista</h2>
        <p className="mt-1 text-xs text-slate-500">
          Intervenciones del periodo frente a la meta diaria.
        </p>
      </div>
      {metaDiaria !== undefined && (
        <span className="rounded-full border border-slate-200 bg-muted px-2 py-0.5 text-[11px] font-medium text-slate-500">
          Meta: {metaDiaria}/día
        </span>
      )}
    </div>
  )
}

/** Tabla equivalente para lectores de pantalla (identity no depende del color). */
function TablaAnalistas({ rows }: { rows: AnalistaChartRow[] }) {
  return (
    <table className="sr-only">
      <caption>Productividad por analista en el periodo</caption>
      <thead>
        <tr>
          <th scope="col">Analista</th>
          <th scope="col">Intervenciones</th>
          <th scope="col">Meta</th>
          <th scope="col">Cumplimiento</th>
          <th scope="col">SLA</th>
          <th scope="col">Bono</th>
        </tr>
      </thead>
      <tbody>
        {rows.map((row) => (
          <tr key={row.analista_id}>
            <th scope="row">{row.nombre_analista}</th>
            <td>{row.intervenciones}</td>
            <td>{row.meta}</td>
            <td>{row.porcentaje}%</td>
            <td>
              {row.sla.dentro}/{row.sla.total}
            </td>
            <td>{row.bono}%</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}

/** Barras horizontales con las intervenciones de cada analista y su bono/SLA. */
export function ProductividadAnalistaChart({ analistas, metaDiaria }: Props) {
  if (analistas.length === 0) {
    return (
      <DashboardCard className="p-5">
        <Encabezado metaDiaria={metaDiaria} />
        <p className="mt-4 text-sm text-slate-400">
          Sin productividad registrada en el periodo.
        </p>
      </DashboardCard>
    )
  }

  const chartData: AnalistaChartRow[] = analistas
    .map((a) => ({
      analista_id: a.analista_id,
      nombre_analista: a.nombre,
      intervenciones: a.total,
      meta: a.meta,
      porcentaje: a.porcentaje,
      bono: a.bono,
      sla: a.sla,
    }))
    .sort((a, b) => b.intervenciones - a.intervenciones)

  // 50px por analista para que las barras no se aplasten; el scroll interno
  // de la tarjeta se encarga de los periodos con muchos técnicos.
  const chartHeight = Math.max(250, chartData.length * 50)

  return (
    <DashboardCard className="p-5">
      <Encabezado metaDiaria={metaDiaria} />

      <div
        aria-label="Gráfico de barras horizontales con las intervenciones por analista"
        className="mt-2 max-h-[400px] w-full overflow-y-auto overflow-x-hidden pr-2"
        role="img"
      >
        <ResponsiveContainer height={chartHeight} width="100%">
          <BarChart
            data={chartData}
            layout="vertical"
            margin={{ top: 4, right: 36, bottom: 4, left: 0 }}
          >
            <CartesianGrid horizontal={false} stroke="#e2e8f0" />
            <XAxis
              allowDecimals={false}
              axisLine={false}
              domain={[0, 'dataMax']}
              tick={{ fill: '#64748b', fontSize: 11 }}
              tickLine={false}
              type="number"
            />
            <YAxis
              axisLine={false}
              dataKey="nombre_analista"
              tick={{ fill: '#475569', fontSize: 12 }}
              tickFormatter={truncarNombre}
              tickLine={false}
              type="category"
              width={124}
            />
            <Tooltip
              cursor={{ fill: '#f1f5f9' }}
              content={({ active, payload }) => {
                if (!active || !payload?.length) return null
                const row = payload[0]?.payload as AnalistaChartRow | undefined
                if (!row) return null
                return (
                  <div className="rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur-sm">
                    <div className="mb-1 flex items-center gap-2">
                      <span
                        className="h-2.5 w-2.5 rounded-full"
                        style={{ backgroundColor: COLOR_BARRA }}
                      />
                      <p className="text-xs font-semibold text-slate-700">{row.nombre_analista}</p>
                    </div>
                    <p className="text-lg font-bold text-slate-900">
                      {row.intervenciones}{' '}
                      <span className="text-xs font-medium text-slate-500">intervenciones</span>
                    </p>
                    <p className="mt-1 font-mono text-[11px] text-slate-500">
                      Meta {row.meta} · {row.porcentaje}% · SLA {row.sla.dentro}/{row.sla.total}
                    </p>
                    <p className="mt-0.5 text-[11px] font-medium text-slate-600">
                      {row.bono > 0 ? `Bono ${row.bono}%` : 'Sin bono'}
                    </p>
                  </div>
                )
              }}
            />
            <Bar
              dataKey="intervenciones"
              fill={COLOR_BARRA}
              maxBarSize={22}
              radius={[0, 4, 4, 0]}
            >
              <LabelList
                dataKey="intervenciones"
                fill="#334155"
                fontSize={11}
                position="right"
              />
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </div>

      <TablaAnalistas rows={chartData} />
    </DashboardCard>
  )
}
