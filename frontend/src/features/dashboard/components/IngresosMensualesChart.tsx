import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { DashboardCard } from './DashboardCard'

export interface IngresoMensual {
  mes: string
  cantidad: number
}

/**
 * Mock temporal mientras el backend no expone la serie agregada por mes.
 * Reemplazar por `GET /api/dashboard/ingresos-mensuales` cuando exista.
 */
export const INGRESOS_MENSUALES_MOCK: IngresoMensual[] = [
  { mes: 'Ene', cantidad: 132 },
  { mes: 'Feb', cantidad: 148 },
  { mes: 'Mar', cantidad: 141 },
  { mes: 'Abr', cantidad: 169 },
  { mes: 'May', cantidad: 158 },
  { mes: 'Jun', cantidad: 176 },
  { mes: 'Jul', cantidad: 190 },
  { mes: 'Ago', cantidad: 183 },
  { mes: 'Sep', cantidad: 201 },
  { mes: 'Oct', cantidad: 214 },
  { mes: 'Nov', cantidad: 208 },
  { mes: 'Dic', cantidad: 236 },
]

/** Tabla equivalente para lectores de pantalla (la serie no depende del color). */
function TablaIngresos({ data }: { data: IngresoMensual[] }) {
  return (
    <table className="sr-only">
      <caption>Total de equipos ingresados por mes (datos preliminares)</caption>
      <thead>
        <tr>
          <th scope="col">Mes</th>
          <th scope="col">Equipos ingresados</th>
        </tr>
      </thead>
      <tbody>
        {data.map((item) => (
          <tr key={item.mes}>
            <th scope="row">{item.mes}</th>
            <td>{item.cantidad}</td>
          </tr>
        ))}
      </tbody>
    </table>
  )
}

/** Barras mensuales de equipos ingresados al taller (mock mientras llega la API). */
export function IngresosMensualesChart({ data = INGRESOS_MENSUALES_MOCK }: { data?: IngresoMensual[] }) {
  return (
    <DashboardCard className="flex flex-col p-5">
      <div className="flex flex-wrap items-baseline justify-between gap-2">
        <div>
          <h2 className="text-sm font-semibold text-slate-700">Total ingresados por mes</h2>
          <p className="mt-1 text-xs text-slate-500">
            Equipos que ingresaron al taller en el año en curso.
          </p>
        </div>
        <span className="rounded-full border border-line bg-muted px-2 py-0.5 text-[11px] font-medium text-slate-500">
          Datos preliminares
        </span>
      </div>

      <div
        className="mt-4 w-full"
        role="img"
        aria-label="Gráfico de barras con el total de equipos ingresados por mes"
      >
        <ResponsiveContainer height={300} width="100%">
          <BarChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -8 }}>
            <CartesianGrid stroke="#e2e8f0" vertical={false} />
            <XAxis
              axisLine={{ stroke: '#e2e8f0' }}
              dataKey="mes"
              dy={6}
              tick={{ fill: '#64748b', fontSize: 12 }}
              tickLine={false}
            />
            <YAxis
              allowDecimals={false}
              axisLine={false}
              tick={{ fill: '#64748b', fontSize: 12 }}
              tickLine={false}
              width={44}
            />
            <Tooltip
              cursor={{ fill: '#f1f5f9' }}
              contentStyle={{
                background: '#ffffff',
                border: '1px solid #e2e8f0',
                borderRadius: 8,
                boxShadow: '0 4px 12px rgba(15, 23, 42, 0.08)',
                fontSize: 12,
              }}
              formatter={(value) => [
                `${Number(value).toLocaleString('es-VE')} equipos`,
                'Ingresados',
              ]}
            />
            <Bar
              dataKey="cantidad"
              fill="#2563eb"
              maxBarSize={24}
              name="Ingresados"
              radius={[4, 4, 0, 0]}
            />
          </BarChart>
        </ResponsiveContainer>
      </div>

      <TablaIngresos data={data} />
    </DashboardCard>
  )
}
