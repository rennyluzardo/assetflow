import { useState } from 'react'
import { cn } from '@/lib/cn'
import { useAlternarUbicacion, useAlternarZona, useZonasGestion } from './api'
import { SEDE_LABELS } from './constantes'
import { UbicacionFormModal } from './UbicacionFormModal'
import { ZonaFormModal } from './ZonaFormModal'
import type { Ubicacion, Zona } from './types'

/** Píldora de actividad de una zona o ubicación. */
function BadgeActivo({ activo }: { activo: boolean }) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium',
        activo
          ? 'border-estado-verde-line bg-estado-verde text-estado-verde-ink'
          : 'border-line bg-muted text-slate-500',
      )}
    >
      {activo ? 'Activa' : 'Inactiva'}
    </span>
  )
}

/** Maestro de Zonas y Ubicaciones: lista de zonas y detalle con ABM. */
export function ZonasUbicacionesScreen() {
  const { data: zonas, isLoading, isError } = useZonasGestion()
  const alternarZona = useAlternarZona()
  const alternarUbicacion = useAlternarUbicacion()

  const [seleccionadaId, setSeleccionadaId] = useState<number | null>(null)
  const [modalZona, setModalZona] = useState<Zona | null | 'nueva'>(null)
  const [modalUbicacion, setModalUbicacion] = useState<Ubicacion | null | 'nueva'>(null)

  const seleccionada = zonas?.find((zona) => zona.id === seleccionadaId) ?? null

  return (
    <div className="flex h-dvh flex-col gap-4 overflow-hidden p-6">
      <header className="flex shrink-0 flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Zonas y Ubicaciones</h1>
          <p className="mt-1 text-sm text-slate-500">
            Maestro de sedes y ubicaciones que alimentan los destinos de traslado.
          </p>
        </div>
        <button
          type="button"
          onClick={() => setModalZona('nueva')}
          className="shrink-0 rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800"
        >
          Nueva zona
        </button>
      </header>

      <div className="flex min-h-0 flex-1 gap-4">
        <aside className="flex w-72 shrink-0 flex-col overflow-hidden rounded-lg border border-line bg-surface">
          <div className="border-b border-line px-4 py-3 text-xs font-medium uppercase tracking-wide text-slate-500">
            Zonas
          </div>
          <div className="min-h-0 flex-1 overflow-y-auto">
            {isLoading ? (
              <p className="p-4 text-sm text-slate-400">Cargando zonas…</p>
            ) : isError ? (
              <p className="p-4 text-sm text-estado-terracota-ink">No se pudieron cargar las zonas.</p>
            ) : (
              (zonas ?? []).map((zona) => (
                <button
                  key={zona.id}
                  type="button"
                  onClick={() => setSeleccionadaId(zona.id)}
                  className={cn(
                    'flex w-full items-center justify-between gap-2 border-b border-line px-4 py-3 text-left transition-colors',
                    seleccionadaId === zona.id ? 'bg-muted' : 'hover:bg-muted/60',
                  )}
                >
                  <div className="min-w-0">
                    <p className="truncate font-mono text-sm font-medium text-slate-900">{zona.nombre}</p>
                    <p className="text-xs text-slate-500">
                      {SEDE_LABELS[zona.sede] ?? zona.sede} · {zona.ubicaciones.length} ubicaciones
                    </p>
                  </div>
                  <BadgeActivo activo={zona.activo} />
                </button>
              ))
            )}
          </div>
        </aside>

        <section className="flex min-w-0 flex-1 flex-col overflow-hidden rounded-lg border border-line bg-surface">
          {!seleccionada ? (
            <div className="flex flex-1 items-center justify-center p-8 text-sm text-slate-400">
              Seleccione una zona para ver sus ubicaciones.
            </div>
          ) : (
            <>
              <div className="flex shrink-0 items-start justify-between gap-4 border-b border-line px-5 py-4">
                <div>
                  <div className="flex items-center gap-3">
                    <h2 className="font-mono text-lg font-semibold tracking-tight text-slate-900">
                      {seleccionada.nombre}
                    </h2>
                    <BadgeActivo activo={seleccionada.activo} />
                  </div>
                  <p className="mt-0.5 text-sm text-slate-500">{SEDE_LABELS[seleccionada.sede] ?? seleccionada.sede}</p>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                  <button
                    type="button"
                    onClick={() => setModalZona(seleccionada)}
                    className="rounded-md border border-line px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900"
                  >
                    Editar
                  </button>
                  <button
                    type="button"
                    onClick={() =>
                      alternarZona.mutate({ id: seleccionada.id, activo: !seleccionada.activo })
                    }
                    disabled={alternarZona.isPending}
                    className="rounded-md border border-line px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:opacity-40"
                  >
                    {seleccionada.activo ? 'Inactivar' : 'Activar'}
                  </button>
                </div>
              </div>

              <div className="flex shrink-0 items-center justify-between border-b border-line px-5 py-3">
                <span className="text-xs font-medium uppercase tracking-wide text-slate-500">Ubicaciones</span>
                <button
                  type="button"
                  onClick={() => setModalUbicacion('nueva')}
                  className="rounded-md bg-slate-900 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-slate-800"
                >
                  Nueva ubicación
                </button>
              </div>

              <div className="min-h-0 flex-1 overflow-y-auto">
                {seleccionada.ubicaciones.length === 0 ? (
                  <p className="p-6 text-sm text-slate-400">Sin ubicaciones para esta zona.</p>
                ) : (
                  <ul className="divide-y divide-line">
                    {seleccionada.ubicaciones.map((ubicacion) => (
                      <li key={ubicacion.id} className="flex items-center justify-between gap-3 px-5 py-3">
                        <div className="min-w-0">
                          <p className="font-mono text-sm font-medium text-slate-900">{ubicacion.nombre}</p>
                        </div>
                        <div className="flex shrink-0 items-center gap-2">
                          <BadgeActivo activo={ubicacion.activo} />
                          <button
                            type="button"
                            onClick={() => setModalUbicacion(ubicacion)}
                            className="rounded-md border border-line px-2 py-1 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900"
                          >
                            Editar
                          </button>
                          <button
                            type="button"
                            onClick={() =>
                              alternarUbicacion.mutate({ id: ubicacion.id, activo: !ubicacion.activo })
                            }
                            disabled={alternarUbicacion.isPending}
                            className="rounded-md border border-line px-2 py-1 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:opacity-40"
                          >
                            {ubicacion.activo ? 'Inactivar' : 'Activar'}
                          </button>
                        </div>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            </>
          )}
        </section>
      </div>

      {modalZona === 'nueva' && <ZonaFormModal zona={null} onCerrar={() => setModalZona(null)} />}
      {modalZona !== null && modalZona !== 'nueva' && (
        <ZonaFormModal zona={modalZona} onCerrar={() => setModalZona(null)} />
      )}

      {modalUbicacion === 'nueva' && seleccionada && (
        <UbicacionFormModal
          zonaId={seleccionada.id}
          ubicacion={null}
          onCerrar={() => setModalUbicacion(null)}
        />
      )}
      {modalUbicacion !== null && modalUbicacion !== 'nueva' && seleccionada && (
        <UbicacionFormModal
          zonaId={seleccionada.id}
          ubicacion={modalUbicacion}
          onCerrar={() => setModalUbicacion(null)}
        />
      )}
    </div>
  )
}
