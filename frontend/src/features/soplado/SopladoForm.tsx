import { useEffect, useRef, useState } from 'react'
import type { ChangeEvent, FormEvent } from 'react'
import type { InventarioBusqueda } from '@/types/equipos'
import type { UbicacionDestino } from '@/constants/equipos'
import { UBICACION_LABELS } from '@/constants/equipos'
import { StatusBadge } from '@/components/StatusBadge'
import { cn } from '@/lib/cn'
import { ApiError } from '@/lib/api'
import { useRegistrarSoplado } from './api'
import { TIPO_SUCIEDAD_OPTIONS } from './types'
import type { TipoSuciedad } from './types'

const DESTINOS: readonly UbicacionDestino[] = ['ALMACEN', 'DBAN'] as const

type EstadoFinal = 'COMPLETADO' | 'INTERVENIDO'

interface SopladoFormProps {
  equipo: InventarioBusqueda
  onGuardado: (estado: EstadoFinal) => void
}

/** Formulario de limpieza y despacho final del módulo /soplado. */
export function SopladoForm({ equipo, onGuardado }: SopladoFormProps) {
  const [energiza, setEnergiza] = useState(false)
  const [daVideo, setDaVideo] = useState(false)
  const [accesoBios, setAccesoBios] = useState(false)
  const [pastaTermica, setPastaTermica] = useState(false)
  const [tipoSuciedad, setTipoSuciedad] = useState<TipoSuciedad>('polvo')
  const [destino, setDestino] = useState<UbicacionDestino | null>(null)
  const [foto, setFoto] = useState<File | null>(null)
  const [preview, setPreview] = useState<string | null>(null)
  const inputFoto = useRef<HTMLInputElement>(null)

  const registrar = useRegistrarSoplado()

  const estadoFinal: EstadoFinal =
    equipo.equipo_activo?.tipo_gestion === 'INTERVENCION' ? 'INTERVENIDO' : 'COMPLETADO'

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

  const puedeGuardar = foto !== null && destino !== null && !registrar.isPending

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (foto === null || destino === null) return

    registrar.mutate(
      {
        placa_id: equipo.placa_id,
        energiza,
        da_video: daVideo,
        acceso_bios: accesoBios,
        tipo_suciedad: tipoSuciedad,
        aplica_pasta_termica: pastaTermica,
        foto_ruta: foto,
        ubicacion_destino: destino,
      },
      { onSuccess: () => onGuardado(estadoFinal) },
    )
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="max-w-2xl space-y-6 rounded-lg border border-line bg-surface p-5"
    >
      <div>
        <h2 className="text-sm font-medium text-slate-700">Inspección de limpieza</h2>
        <p className="mt-1 text-xs text-slate-500">Registre los hallazgos del equipo escaneado.</p>
      </div>

      <section className="grid grid-cols-1 gap-2 sm:grid-cols-2">
        <Toggle label="Energiza" checked={energiza} onChange={setEnergiza} disabled={registrar.isPending} />
        <Toggle label="Da video" checked={daVideo} onChange={setDaVideo} disabled={registrar.isPending} />
        <Toggle
          label="Ingresa al BIOS"
          checked={accesoBios}
          onChange={setAccesoBios}
          disabled={registrar.isPending}
        />
        <Toggle
          label="Aplica pasta térmica"
          checked={pastaTermica}
          onChange={setPastaTermica}
          disabled={registrar.isPending}
        />
      </section>

      <section>
        <h3 className="text-sm font-medium text-slate-700">Tipo de suciedad</h3>
        <div className="mt-2 grid grid-cols-2 gap-2">
          {TIPO_SUCIEDAD_OPTIONS.map((opcion) => (
            <button
              key={opcion.value}
              type="button"
              disabled={registrar.isPending}
              onClick={() => setTipoSuciedad(opcion.value)}
              className={cn(
                'rounded-md border px-3 py-2 text-left text-sm font-medium transition-colors',
                tipoSuciedad === opcion.value
                  ? 'border-slate-400 bg-muted text-slate-900'
                  : 'border-line bg-surface text-slate-600 hover:border-slate-300 hover:bg-muted',
              )}
            >
              {opcion.label}
            </button>
          ))}
        </div>
      </section>

      <section>
        <h3 className="text-sm font-medium text-slate-700">
          Evidencia fotográfica <span className="text-estado-terracota-ink">*</span>
        </h3>
        <input
          ref={inputFoto}
          type="file"
          accept="image/jpeg,image/png,image/webp"
          onChange={handleFoto}
          disabled={registrar.isPending}
          className="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border file:border-line file:bg-canvas file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-muted"
        />
        {preview && (
          <div className="mt-3 flex items-center gap-3">
            <img
              src={preview}
              alt="Vista previa de la evidencia"
              className="h-24 w-24 rounded-md border border-line object-cover"
            />
            <button
              type="button"
              disabled={registrar.isPending}
              onClick={quitarFoto}
              className="text-xs font-medium text-slate-500 underline-offset-2 hover:underline"
            >
              Quitar foto
            </button>
          </div>
        )}
      </section>

      <section>
        <h3 className="text-sm font-medium text-slate-700">Ubicación de destino</h3>
        <div className="mt-2 grid grid-cols-2 gap-2">
          {DESTINOS.map((opcion) => (
            <button
              key={opcion}
              type="button"
              disabled={registrar.isPending}
              onClick={() => setDestino(opcion)}
              className={cn(
                'rounded-md border px-4 py-3 text-left text-sm font-medium transition-colors',
                destino === opcion
                  ? 'border-slate-400 bg-muted text-slate-900'
                  : 'border-line bg-surface text-slate-600 hover:border-slate-300 hover:bg-muted',
              )}
            >
              {UBICACION_LABELS[opcion]}
            </button>
          ))}
        </div>
        {destino && (
          <p className="mt-2 flex items-center gap-2 text-sm text-slate-600">
            Al despachar pasará a: <StatusBadge estado={estadoFinal} />
          </p>
        )}
      </section>

      {registrar.isError && (
        <p className="text-sm text-estado-terracota-ink">
          {registrar.error instanceof ApiError
            ? registrar.error.message
            : 'No se pudo registrar el soplado.'}
        </p>
      )}

      <button
        type="submit"
        disabled={!puedeGuardar}
        className="w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
      >
        {registrar.isPending ? 'Despachando…' : 'Registrar soplado y despachar'}
      </button>
    </form>
  )
}

function Toggle({
  label,
  checked,
  onChange,
  disabled,
}: {
  label: string
  checked: boolean
  onChange: (value: boolean) => void
  disabled?: boolean
}) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      disabled={disabled}
      onClick={() => onChange(!checked)}
      className={cn(
        'flex items-center justify-between rounded-md border px-4 py-3 text-left text-sm font-medium transition-colors',
        checked
          ? 'border-slate-400 bg-muted text-slate-900'
          : 'border-line bg-surface text-slate-600 hover:border-slate-300 hover:bg-muted',
      )}
    >
      <span>{label}</span>
      <span className={cn('text-xs', checked ? 'text-estado-verde-ink' : 'text-slate-400')}>
        {checked ? 'Sí' : 'No'}
      </span>
    </button>
  )
}
