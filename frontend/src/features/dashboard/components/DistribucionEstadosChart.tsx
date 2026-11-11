import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'

export interface EstadoData {
  name: string
  value: number
  color?: string
}

interface Props {
  data: EstadoData[]
}

/**
 * Colores por estado. Se mantiene un orden fijo para que dos porciones
 * contiguas del anillo nunca compartan tonos difíciles de distinguir.
 * El gris de «Sin estado» es intencionalmente neutro.
 */
export const ESTADOS_COLORS: Record<string, string> = {
  Completado: '#10b981', // emerald-500
  Intervenido: '#0ea5e9', // sky-500
  'Por soplar': '#f59e0b', // amber-500
  'Pendiente garantía': '#6366f1', // indigo-500
  'Baja (No sirve)': '#ef4444', // red-500
  'Por reacondicionar': '#84cc16', // lime-500
  Repotenciado: '#8b5cf6', // violet-500
  'Sin estado': '#94a3b8', // slate-400
}

/** Orden canónico de las porciones (también define la adyacencia de colores). */
const ORDEN_ESTADOS = Object.keys(ESTADOS_COLORS)

const COLOR_FALLBACK = '#cbd5e1' // slate-300

function ordenarPorEstado(a: EstadoData, b: EstadoData): number {
  const ia = ORDEN_ESTADOS.indexOf(a.name)
  const ib = ORDEN_ESTADOS.indexOf(b.name)
  return (ia === -1 ? ORDEN_ESTADOS.length : ia) - (ib === -1 ? ORDEN_ESTADOS.length : ib)
}

/**
 * Gráfico de dona del KPI «Distribución por estado». El total vive al centro y
 * la leyenda expone nombre + cantidad, de modo que ningún valor depende solo
 * del color o del tooltip.
 */
export function DistribucionEstadosChart({ data }: Props) {
  const chartData = data
    .filter((item) => item.value > 0)
    .map((item) => ({
      ...item,
      color: item.color || ESTADOS_COLORS[item.name] || COLOR_FALLBACK,
    }))
    .sort(ordenarPorEstado)

  const total = chartData.reduce((suma, item) => suma + item.value, 0)

  if (chartData.length === 0) {
    return (
      <div className="flex flex-col rounded-lg border border-line bg-surface p-5">
        <h2 className="text-sm font-semibold text-slate-700">Distribución por estado</h2>
        <p className="mt-4 text-sm text-slate-400">
          Sin estados registrados en el periodo seleccionado.
        </p>
      </div>
    )
  }

  return (
    <div className="flex flex-col rounded-lg border border-line bg-surface p-5">
      <div className="flex items-baseline justify-between gap-3">
        <h2 className="text-sm font-semibold text-slate-700">Distribución por estado</h2>
        <span className="font-mono text-xs text-slate-400">{total} equipos</span>
      </div>

      <div
        aria-label="Gráfico de dona con la distribución de equipos por estado"
        className="relative mt-2 h-56 w-full"
        role="img"
      >
        <ResponsiveContainer height="100%" width="100%">
          <PieChart>
            <Pie
              cx="50%"
              cy="50%"
              data={chartData}
              dataKey="value"
              innerRadius="64%"
              nameKey="name"
              outerRadius="88%"
              paddingAngle={2}
              stroke="#ffffff"
              strokeWidth={2}
            >
              {chartData.map((entry) => (
                <Cell fill={entry.color} key={entry.name} />
              ))}
            </Pie>
            <Tooltip
              content={({ active, payload }) => {
                if (!active || !payload?.length) return null
                const item = payload[0]?.payload as EstadoData | undefined
                if (!item) return null
                return (
                  <div className="rounded-lg border border-line bg-white/95 p-3 shadow-lg backdrop-blur-sm">
                    <div className="mb-1 flex items-center gap-2">
                      <span
                        className="h-3 w-3 rounded-full"
                        style={{ backgroundColor: item.color }}
                      />
                      <p className="text-xs font-medium text-slate-600">{item.name}</p>
                    </div>
                    <p className="text-lg font-bold text-slate-900">
                      {item.value} {item.value === 1 ? 'equipo' : 'equipos'}
                    </p>
                  </div>
                )
              }}
            />
          </PieChart>
        </ResponsiveContainer>

        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <span className="font-mono text-2xl font-semibold text-slate-900">{total}</span>
          <span className="text-[11px] uppercase tracking-wide text-slate-400">equipos</span>
        </div>
      </div>

      <ul
        aria-label="Leyenda de la distribución por estado"
        className="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2"
      >
        {chartData.map((item) => (
          <li key={item.name} className="flex items-center justify-between gap-2 text-xs">
            <span className="flex min-w-0 items-center gap-2">
              <span
                className="h-2.5 w-2.5 shrink-0 rounded-full"
                style={{ backgroundColor: item.color }}
              />
              <span className="truncate text-slate-600">{item.name}</span>
            </span>
            <span className="font-mono text-slate-900">{item.value}</span>
          </li>
        ))}
      </ul>
    </div>
  )
}
