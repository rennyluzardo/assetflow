import { useState } from 'react'
import { useAuth } from '@/features/auth/AuthContext'
import { useTraslados } from './api'
import { ZONA_LABELS } from './constantes'
import { RegistrarTrasladoModal } from './RegistrarTrasladoModal'
import { TrasladoDrawer } from './TrasladoDrawer'
import { TrasladoTabla } from './TrasladoTabla'
import type { Traslado, ZonaSede } from './types'

/**
 * Módulo de Traslados en arquitectura Maestro-Detalle: un listado unificado
 * (enviados + recibidos), una Modal central de registro y un Drawer lateral
 * para el detalle y la recepción por sede.
 */
export function TrasladosScreen() {
  const { session } = useAuth()
  const sede = session?.user.sede ?? null

  const [busqueda, setBusqueda] = useState('')
  const [modalAbierto, setModalAbierto] = useState(false)
  const [seleccionado, setSeleccionado] = useState<Traslado | null>(null)

  const { data, isLoading, isError } = useTraslados({ per_page: 1000 })

  const termino = busqueda.trim().toLowerCase()
  const traslados = (data?.data ?? []).filter(
    (traslado) => termino === '' || traslado.codigo.toLowerCase().includes(termino),
  )

  return (
    <div className="flex h-dvh flex-col gap-4 overflow-hidden p-6">
      <header className="flex shrink-0 flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Traslados</h1>
          <p className="mt-1 text-sm text-slate-500">Registro y recepción de lotes entre sedes operativas.</p>
        </div>
        <p className="text-sm text-slate-500">
          Sede física:{' '}
          <span className="font-mono text-slate-900">
            {sede ? ZONA_LABELS[sede as ZonaSede] ?? sede : 'Sin restricción'}
          </span>
        </p>
      </header>

      <div className="flex shrink-0 items-center justify-between gap-4">
        <input
          type="search"
          value={busqueda}
          onChange={(event) => setBusqueda(event.target.value)}
          placeholder="Buscar por Código de traslado..."
          aria-label="Buscar por código de traslado"
          autoComplete="off"
          spellCheck={false}
          className="w-full max-w-sm rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-300"
        />
        <button
          type="button"
          onClick={() => setModalAbierto(true)}
          className="shrink-0 rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800"
        >
          Registrar traslado
        </button>
      </div>

      <div className="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-line bg-surface">
        {isLoading ? (
          <div className="flex flex-1 items-center justify-center p-8 text-sm text-slate-400">
            Cargando traslados…
          </div>
        ) : isError ? (
          <div className="flex flex-1 items-center justify-center p-8 text-sm text-estado-terracota-ink">
            No se pudieron cargar los traslados.
          </div>
        ) : (
          <TrasladoTabla traslados={traslados} onSeleccionar={setSeleccionado} />
        )}
      </div>

      {modalAbierto && <RegistrarTrasladoModal sede={sede} onCerrar={() => setModalAbierto(false)} />}
      {seleccionado && (
        <TrasladoDrawer traslado={seleccionado} sede={sede} onCerrar={() => setSeleccionado(null)} />
      )}
    </div>
  )
}
