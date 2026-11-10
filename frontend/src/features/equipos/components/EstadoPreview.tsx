import { useEffect, useState } from 'react'
import type { EstadoEquipo, UbicacionDestino } from '@/constants/equipos'
import { UBICACION_LABELS } from '@/constants/equipos'
import { StatusBadge } from '@/components/StatusBadge'
import { ApiError } from '@/lib/api'

interface EstadoPreviewProps {
  estado: EstadoEquipo
  destino: UbicacionDestino | null
  observaciones: string
  onObservacionesChange: (value: string) => void
  observacionesRequeridas?: boolean
  foto: File | null
  onFotoChange: (file: File | null) => void
  fotoRequerida?: boolean
  mostrarEvidencia?: boolean
  puedeGuardar?: boolean
  guardando: boolean
  error: ApiError | null
  onGuardar: () => void
}

/** Previsualiza el estado exacto con el que se guardará el registro y permite confirmar. */
export function EstadoPreview({
  estado,
  destino,
  observaciones,
  onObservacionesChange,
  observacionesRequeridas,
  foto,
  onFotoChange,
  fotoRequerida = false,
  mostrarEvidencia = true,
  puedeGuardar,
  guardando,
  error,
  onGuardar,
}: EstadoPreviewProps) {
  const [preview, setPreview] = useState<string | null>(null)

  useEffect(() => {
    if (!foto) {
      setPreview(null)
      return
    }
    const url = URL.createObjectURL(foto)
    setPreview(url)
    return () => URL.revokeObjectURL(url)
  }, [foto])

  const habilitado = puedeGuardar ?? destino !== null

  return (
    <section>
      <h3 className="text-sm font-medium text-slate-700">3. Confirmar registro</h3>

      <div className="mt-3 rounded-md border border-line bg-muted p-4">
        <p className="flex items-center gap-2 text-sm text-slate-600">
          Al guardar pasará a estado:
          <StatusBadge estado={estado} />
        </p>
        {destino && (
          <p className="mt-1.5 font-mono text-xs text-slate-500">
            destino: {destino} · {UBICACION_LABELS[destino]}
          </p>
        )}
      </div>

      <label htmlFor="observaciones" className="mt-4 block text-sm font-medium text-slate-700">
        Observaciones técnicas{' '}
        <span className="font-normal text-slate-400">
          {observacionesRequeridas ? '(obligatorio)' : '(opcional)'}
        </span>
      </label>
      <textarea
        id="observaciones"
        value={observaciones}
        onChange={(event) => onObservacionesChange(event.target.value)}
        disabled={guardando}
        rows={3}
        placeholder="Notas del daño o pieza intervenida…"
        className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-slate-400"
      />

      {mostrarEvidencia && (
        <div className="mt-4">
          <label htmlFor="foto-equipo" className="block text-sm font-medium text-slate-700">
            Evidencia fotográfica{' '}
            <span className={fotoRequerida ? 'font-medium text-estado-terracota-ink' : 'font-normal text-slate-400'}>
              {fotoRequerida ? '(obligatorio)' : '(opcional)'}
            </span>
          </label>
          <input
            id="foto-equipo"
            type="file"
            accept="image/*"
            disabled={guardando}
            onChange={(event) => onFotoChange(event.target.files?.[0] ?? null)}
            className="mt-1 block w-full text-sm text-slate-500 file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
          />
          {foto && (
            <div className="mt-2 flex items-center gap-3">
              {preview && (
                <img
                  src={preview}
                  alt="Vista previa de la evidencia"
                  className="h-16 w-16 rounded-md border border-line object-cover"
                />
              )}
              <div className="min-w-0">
                <p className="truncate font-mono text-xs text-slate-600">{foto.name}</p>
                <button
                  type="button"
                  onClick={() => onFotoChange(null)}
                  disabled={guardando}
                  className="mt-1 text-xs font-medium text-estado-terracota-ink underline-offset-2 hover:underline disabled:opacity-40"
                >
                  Quitar evidencia
                </button>
              </div>
            </div>
          )}
        </div>
      )}

      {error && <p className="mt-2 text-sm text-estado-terracota-ink">{error.message}</p>}

      <button
        type="button"
        disabled={!habilitado || guardando}
        onClick={onGuardar}
        className="mt-4 w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
      >
        {guardando ? 'Guardando…' : 'Guardar registro'}
      </button>
    </section>
  )
}
