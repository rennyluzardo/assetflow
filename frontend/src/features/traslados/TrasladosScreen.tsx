import { useState } from 'react'
import { useTraslados } from './api'
import { BarraFiltros } from './BarraFiltros'
import { Paginacion } from './Paginacion'
import { RegistrarTrasladoModal } from './RegistrarTrasladoModal'
import { TrasladoDrawer } from './TrasladoDrawer'
import { TrasladoTabla } from './TrasladoTabla'
import { useTrasladosUrlState } from './useTrasladosUrlState'

/**
 * Módulo de Traslados en Maestro-Detalle: listado con filtros, modal de
 * apertura y drawer adaptativo según el estado del ciclo de vida.
 */
export function TrasladosScreen() {
  const { search, filtroCodigo, setFiltroCodigo, params, cambiarEstado, irPagina, cambiarTamanio } =
    useTrasladosUrlState()

  const [modalAbierto, setModalAbierto] = useState(false)
  const [seleccionadoId, setSeleccionadoId] = useState<number | null>(null)

  const { data, isLoading, isError } = useTraslados(params)

  return (
    <div className="flex h-dvh flex-col gap-4 overflow-hidden p-6">
      <header className="flex shrink-0 flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Traslados</h1>
          <p className="mt-1 text-sm text-slate-500">
            Apertura, despacho y recepción de lotes entre sedes operativas.
          </p>
        </div>
        <button
          type="button"
          onClick={() => setModalAbierto(true)}
          className="shrink-0 rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800"
        >
          Registrar traslado
        </button>
      </header>

      <BarraFiltros
        codigo={filtroCodigo}
        estado={search.estado}
        onCodigo={setFiltroCodigo}
        onEstado={cambiarEstado}
      />

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
          <>
            <TrasladoTabla
              traslados={data?.data ?? []}
              onSeleccionar={(traslado) => setSeleccionadoId(traslado.id)}
            />
            {data && <Paginacion meta={data.meta} onPagina={irPagina} onTamanio={cambiarTamanio} />}
          </>
        )}
      </div>

      {modalAbierto && <RegistrarTrasladoModal onCerrar={() => setModalAbierto(false)} />}
      {seleccionadoId !== null && (
        <TrasladoDrawer trasladoId={seleccionadoId} onCerrar={() => setSeleccionadoId(null)} />
      )}
    </div>
  )
}
