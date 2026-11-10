import { useState } from 'react'
import type { FormEvent } from 'react'
import { Modal } from '@/components/Modal'
import { useRegistrarTraslado } from './api'
import { ZONA_LABELS } from './constantes'
import { mensajeError } from './componentes'
import { CampoTexto, CampoZona } from './campos'
import type { ZonaSede } from './types'

interface RegistrarTrasladoModalProps {
  sede: string | null
  onCerrar: () => void
}

/**
 * Formulario de creación de un traslado (Código, zona origen/destino y
 * ubicación exacta) presentado en una Modal central global.
 */
export function RegistrarTrasladoModal({ sede, onCerrar }: RegistrarTrasladoModalProps) {
  const [codigo, setCodigo] = useState('')
  const [zonaOrigen, setZonaOrigen] = useState<ZonaSede>('BOGOTA')
  const [zonaDestino, setZonaDestino] = useState<ZonaSede>('MEDELLIN')
  const [ubicacionOrigen, setUbicacionOrigen] = useState('')
  const [ubicacionDestino, setUbicacionDestino] = useState('')

  const registrar = useRegistrarTraslado()
  const codigoLimpio = codigo.trim()

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (codigoLimpio === '') return

    registrar.mutate(
      {
        codigo: codigoLimpio,
        zona_origen: zonaOrigen,
        zona_destino: zonaDestino,
        ...(ubicacionOrigen.trim() !== ''
          ? { ubicacion_origen_exacta: ubicacionOrigen.trim() }
          : {}),
        ...(ubicacionDestino.trim() !== ''
          ? { ubicacion_destino_exacta: ubicacionDestino.trim() }
          : {}),
      },
      { onSuccess: () => onCerrar() },
    )
  }

  return (
    <Modal
      titulo="Registrar traslado"
      descripcion={
        sede
          ? `Seguridad: solo puede originar traslados desde su sede física ${ZONA_LABELS[sede as ZonaSede] ?? sede}.`
          : undefined
      }
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
            disabled={registrar.isPending || codigoLimpio === ''}
            className="flex-1 rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {registrar.isPending ? 'Guardando…' : 'Guardar'}
          </button>
        </>
      }
    >
      <form id="registrar-traslado-form" onSubmit={handleSubmit} className="space-y-4">
        <CampoTexto
          id="traslado-codigo"
          label="Código de traslado"
          value={codigo}
          onChange={setCodigo}
          disabled={registrar.isPending}
          placeholder="Ej. TR-2026-0001"
          mono
        />

        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <CampoZona
            id="traslado-origen"
            label="Zona de origen"
            value={zonaOrigen}
            onChange={setZonaOrigen}
            disabled={registrar.isPending}
          />
          <CampoZona
            id="traslado-destino"
            label="Zona de destino"
            value={zonaDestino}
            onChange={setZonaDestino}
            disabled={registrar.isPending}
          />
        </div>

        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <CampoTexto
            id="ubicacion-origen"
            label="Ubicación exacta de origen"
            value={ubicacionOrigen}
            onChange={setUbicacionOrigen}
            disabled={registrar.isPending}
            placeholder="Ej. Bodega Norte"
          />
          <CampoTexto
            id="ubicacion-destino"
            label="Ubicación exacta de destino"
            value={ubicacionDestino}
            onChange={setUbicacionDestino}
            disabled={registrar.isPending}
            placeholder="Ej. Sala 2"
          />
        </div>

        {registrar.isError && (
          <p className="text-sm text-estado-terracota-ink">{mensajeError(registrar.error)}</p>
        )}
      </form>
    </Modal>
  )
}
