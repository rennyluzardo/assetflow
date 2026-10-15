import type { EstadoEquipo, UbicacionDestino } from '@/constants/equipos'
import { UBICACION_LABELS } from '@/constants/equipos'
import { StatusBadge } from '@/components/StatusBadge'
import { ApiError } from '@/lib/api'

interface EstadoPreviewProps {
  estado: EstadoEquipo
  destino: UbicacionDestino | null
  observaciones: string
  onObservacionesChange: (value: string) => void
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
  guardando,
  error,
  onGuardar,
}: EstadoPreviewProps) {
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
        Observaciones técnicas <span className="font-normal text-slate-400">(opcional)</span>
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

      {error && <p className="mt-2 text-sm text-estado-terracota-ink">{error.message}</p>}

      <button
        type="button"
        disabled={destino === null || guardando}
        onClick={onGuardar}
        className="mt-4 w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
      >
        {guardando ? 'Guardando…' : 'Guardar registro'}
      </button>
    </section>
  )
}
