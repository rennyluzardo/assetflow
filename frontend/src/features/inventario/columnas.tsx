import { Link } from '@tanstack/react-router'
import { createColumnHelper } from '@tanstack/react-table'
import { UBICACION_LABELS } from '@/constants/equipos'
import { CeldaEstado } from './components/CeldaEstado'
import { inventarioFeatures } from './table/features'
import type { InventarioItem } from './types'

const helper = createColumnHelper<typeof inventarioFeatures, InventarioItem>()

/**
 * Definiciones de columna de la tabla maestra.
 *
 * Se declaran a nivel de módulo: TanStack Table invalida los row models si
 * `columns` cambia de identidad entre renders.
 */
export const COLUMNAS_INVENTARIO = helper.columns([
  helper.accessor('placa_id', {
    header: 'Placa',
    cell: (info) => <span className="font-mono text-xs font-medium text-slate-900">{info.getValue()}</span>,
  }),
  helper.accessor('serial', {
    header: 'Serial',
    cell: (info) => <span className="font-mono text-xs text-slate-500">{info.getValue()}</span>,
  }),
  helper.accessor('descripcion', {
    header: 'Descripción',
    cell: (info) => <span className="text-xs text-slate-700">{info.getValue()}</span>,
  }),
  helper.accessor('marca', {
    header: 'Marca',
    cell: (info) => <span className="text-xs text-slate-600">{info.getValue() ?? '—'}</span>,
  }),
  helper.accessor('num_traslado', {
    header: 'Traslado',
    cell: (info) => <span className="font-mono text-xs text-slate-600">{info.getValue()}</span>,
  }),
  helper.accessor('ubicacion_actual', {
    header: 'Ubicación',
    cell: (info) => {
      const ubicacion = info.getValue()
      return (
        <span className="text-xs text-slate-600">
          {ubicacion ? (UBICACION_LABELS[ubicacion] ?? ubicacion) : '—'}
        </span>
      )
    },
  }),
  helper.accessor('estado_actual', {
    header: 'Estado',
    cell: (info) => <CeldaEstado estado={info.getValue()} />,
  }),
  helper.accessor('intervenido', {
    header: 'Cierre',
    cell: (info) =>
      info.getValue() ? (
        <span className="text-estado-verde-ink">✓</span>
      ) : (
        <span className="text-slate-400">—</span>
      ),
  }),
  helper.display({
    id: 'acciones',
    header: 'Acción',
    enableSorting: false,
    cell: (info) => (
      <Link
        to="/trazabilidad"
        search={{ placa_id: info.row.original.placa_id }}
        className="rounded-md border border-line px-2 py-1 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900"
      >
        Trazar
      </Link>
    ),
  }),
])
