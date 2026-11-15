import { useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Modal } from '@/components/Modal'
import { useRegistrarTraslado, useZonasActivas } from './api'
import { mensajeError } from './componentes'
import { CampoTextarea, SelectorUbicacion, SelectorZona } from './campos'

interface RegistrarTrasladoModalProps {
  onCerrar: () => void
}

/**
 * Formulario de apertura de un traslado: el código es autogenerado y el origen
 * nace vacío; solo se elige el destino (zona + ubicación) y una observación.
 */
export function RegistrarTrasladoModal({ onCerrar }: RegistrarTrasladoModalProps) {
  const { data: zonas, isLoading } = useZonasActivas()
  const registrar = useRegistrarTraslado()

  const [zonaDestino, setZonaDestino] = useState('')
  const [ubicacionDestino, setUbicacionDestino] = useState('')
  const [observaciones, setObservaciones] = useState('')

  const ubicaciones = useMemo(
    () => zonas?.find((zona) => zona.nombre === zonaDestino)?.ubicaciones ?? [],
    [zonas, zonaDestino],
  )

  const listo = zonaDestino !== '' && ubicacionDestino !== ''

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (!listo) return

    registrar.mutate(
      {
        zona_destino: zonaDestino,
        ubicacion_destino: ubicacionDestino,
        ...(observaciones.trim() !== '' ? { observaciones: observaciones.trim() } : {}),
      },
      { onSuccess: () => onCerrar() },
    )
  }

  return (
    <Modal
      titulo="Registrar traslado"
      descripcion="El código se genera automáticamente y el origen se define con el primer equipo escaneado."
      onCerrar={onCerrar}
      footer={
        <>
          <button
            type="button"
            onClick={onCerrar}
            disabled={registrar.isPending}
            className="flex-1 rounded-md border border-line bg-canvas px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:opacity-40"
          >
            Cancelar
          </button>
          <button
            type="submit"
            form="registrar-traslado-form"
            disabled={registrar.isPending || !listo || isLoading}
            className="flex-1 rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {registrar.isPending ? 'Guardando…' : 'Abrir traslado'}
          </button>
        </>
      }
    >
      <form id="registrar-traslado-form" onSubmit={handleSubmit} className="space-y-4">
        {isLoading ? (
          <p className="text-sm text-slate-400">Cargando zonas…</p>
        ) : (
          <>
            <SelectorZona
              id="traslado-zona-destino"
              label="Zona de destino"
              zonas={zonas ?? []}
              value={zonaDestino}
              onChange={(zona) => {
                setZonaDestino(zona?.nombre ?? '')
                setUbicacionDestino('')
              }}
              disabled={registrar.isPending}
            />

            <SelectorUbicacion
              id="traslado-ubicacion-destino"
              label="Ubicación de destino"
              ubicaciones={ubicaciones}
              value={ubicacionDestino}
              onChange={setUbicacionDestino}
              disabled={registrar.isPending}
            />

            <CampoTextarea
              id="traslado-observaciones"
              label="Observaciones (opcional)"
              value={observaciones}
              onChange={setObservaciones}
              disabled={registrar.isPending}
              placeholder="Detalle del lote, motivo del envío…"
            />
          </>
        )}

        {registrar.isError && (
          <p className="text-sm text-estado-terracota-ink">{mensajeError(registrar.error)}</p>
        )}
      </form>
    </Modal>
  )
}
