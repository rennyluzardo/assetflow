import { useEffect, useRef, useState } from 'react'
import type { ChangeEvent } from 'react'
import type { PortatilActivo, PortatilRegistro } from './types'
import { ESTADO_LABELS } from '@/constants/equipos'
import { StatusBadge } from '@/components/StatusBadge'
import { ApiError } from '@/lib/api'
import { useCerrarPortatil, useSubirFotoPortatil } from './api'

interface CierrePortatilModalProps {
  registro: PortatilActivo
  placaId: string
  onCerrado: (registro: PortatilRegistro) => void
  onCancelar: () => void
}

/**
 * Punto de Control de Cierre del registro único: muta el registro abierto
 * (PENDIENTE_GARANTIA / POR_REACONDICIONAR) a INTERVENIDO en Almacén General.
 */
export function CierrePortatilModal({ registro, placaId, onCerrado, onCancelar }: CierrePortatilModalProps) {
  const [reparadoPor, setReparadoPor] = useState('')
  const [foto, setFoto] = useState<File | null>(null)
  const [preview, setPreview] = useState<string | null>(null)
  const inputFoto = useRef<HTMLInputElement>(null)

  const cerrar = useCerrarPortatil()
  const subirFoto = useSubirFotoPortatil()

  useEffect(() => {
    return () => {
      if (preview) URL.revokeObjectURL(preview)
    }
  }, [preview])

  const handleFoto = (event: ChangeEvent<HTMLInputElement>) => {
    const archivo = event.target.files?.[0] ?? null
    setFoto(archivo)
    setPreview(archivo ? URL.createObjectURL(archivo) : null)
  }

  const quitarFoto = () => {
    if (inputFoto.current) inputFoto.current.value = ''
    setFoto(null)
    setPreview(null)
  }

  const puedeCerrar = reparadoPor.trim() !== '' && !cerrar.isPending && !subirFoto.isPending

  const handleCerrar = () => {
    if (!puedeCerrar) return

    const payload = {
      ubicacion_destino: 'ALMACEN' as const,
      reparado_por: reparadoPor.trim(),
    }

    if (foto) {
      subirFoto.mutate(
        { id: registro.id, file: foto },
        {
          onSuccess: (subido) => {
            cerrar.mutate(
              {
                id: registro.id,
                payload: { ...payload, foto_evidencia: subido.foto_evidencia ?? undefined },
              },
              { onSuccess: (cerrado) => onCerrado(cerrado) },
            )
          },
        },
      )
      return
    }

    cerrar.mutate({ id: registro.id, payload }, { onSuccess: (cerrado) => onCerrado(cerrado) })
  }

  const error = cerrar.error ?? subirFoto.error

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-label="Cerrar registro de portátil"
    >
      <div className="w-full max-w-md rounded-lg border border-line bg-surface p-5">
        <header className="mb-4">
          <h2 className="text-lg font-semibold tracking-tight text-slate-900">
            Cerrar registro de portátil
          </h2>
          <p className="mt-1 font-mono text-xs text-slate-500">{placaId}</p>
        </header>

        <div className="mb-4 rounded-md border border-estado-ambar-line bg-estado-ambar px-4 py-3">
          <p className="flex items-center gap-2 text-sm text-estado-ambar-ink">
            Registro único abierto
            <StatusBadge estado={registro.estado} />
          </p>
          <p className="mt-1 text-xs text-estado-ambar-ink">
            {ESTADO_LABELS[registro.estado]} — cierre hacia Almacén General.
          </p>
        </div>

        <div className="space-y-4">
          <div>
            <label htmlFor="reparado-por" className="mb-1.5 block text-sm font-medium text-slate-700">
              Reparado por <span className="text-estado-terracota-ink">*</span>
            </label>
            <input
              id="reparado-por"
              type="text"
              value={reparadoPor}
              onChange={(event) => setReparadoPor(event.target.value)}
              disabled={cerrar.isPending || subirFoto.isPending}
              placeholder="Técnico o taller responsable"
              className="w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-slate-400"
            />
          </div>

          <div>
            <p className="mb-1.5 text-sm font-medium text-slate-700">
              Evidencia fotográfica <span className="font-normal text-slate-400">(opcional)</span>
            </p>
            <input
              ref={inputFoto}
              type="file"
              accept="image/jpeg,image/png,image/webp"
              onChange={handleFoto}
              disabled={cerrar.isPending || subirFoto.isPending}
              className="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border file:border-line file:bg-canvas file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-muted"
            />
            {preview && (
              <div className="mt-3 flex items-center gap-3">
                <img
                  src={preview}
                  alt="Vista previa de la evidencia"
                  className="h-20 w-20 rounded-md border border-line object-cover"
                />
                <button
                  type="button"
                  disabled={cerrar.isPending || subirFoto.isPending}
                  onClick={quitarFoto}
                  className="text-xs font-medium text-slate-500 underline-offset-2 hover:underline"
                >
                  Quitar foto
                </button>
              </div>
            )}
          </div>

          {error && (
            <p className="text-sm text-estado-terracota-ink">
              {error instanceof ApiError ? error.message : 'No se pudo cerrar el registro.'}
            </p>
          )}
        </div>

        <footer className="mt-6 flex gap-3">
          <button
            type="button"
            onClick={onCancelar}
            disabled={cerrar.isPending || subirFoto.isPending}
            className="flex-1 rounded-md border border-line bg-canvas px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:opacity-40"
          >
            Cancelar
          </button>
          <button
            type="button"
            onClick={handleCerrar}
            disabled={!puedeCerrar}
            className="flex-1 rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {cerrar.isPending || subirFoto.isPending ? 'Cerrando…' : 'Cerrar hacia Almacén'}
          </button>
        </footer>
      </div>
    </div>
  )
}
