import { useState } from 'react'
import type { FormEvent } from 'react'
import { Modal } from '@/components/Modal'
import { ApiError } from '@/lib/api'
import { useActualizarZona, useCrearZona } from './api'
import { CAMPO, SEDES, SEDE_LABELS } from './constantes'
import type { Zona } from './types'

interface ZonaFormModalProps {
  zona: Zona | null
  onCerrar: () => void
}

function mensaje(error: ApiError | null): string | null {
  if (!error) return null
  return Object.values(error.errors).flat()[0] ?? error.message
}

/** Alta o edición de una zona (sede + nombre). */
export function ZonaFormModal({ zona, onCerrar }: ZonaFormModalProps) {
  const [sede, setSede] = useState(zona?.sede ?? 'COLOMBIA')
  const [nombre, setNombre] = useState(zona?.nombre ?? '')

  const crear = useCrearZona()
  const actualizar = useActualizarZona()
  const mutacion = zona ? actualizar : crear
  const error = mutacion.error

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    const nombreLimpio = nombre.trim()
    if (nombreLimpio === '') return

    if (zona) {
      actualizar.mutate({ id: zona.id, sede, nombre: nombreLimpio }, { onSuccess: onCerrar })
    } else {
      crear.mutate({ sede, nombre: nombreLimpio }, { onSuccess: onCerrar })
    }
  }

  return (
    <Modal
      titulo={zona ? 'Editar zona' : 'Nueva zona'}
      onCerrar={onCerrar}
      footer={
        <>
          <button
            type="button"
            onClick={onCerrar}
            disabled={mutacion.isPending}
            className="flex-1 rounded-md border border-line bg-canvas px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:opacity-40"
          >
            Cancelar
          </button>
          <button
            type="submit"
            form="zona-form"
            disabled={mutacion.isPending || nombre.trim() === ''}
            className="flex-1 rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {mutacion.isPending ? 'Guardando…' : 'Guardar'}
          </button>
        </>
      }
    >
      <form id="zona-form" onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label htmlFor="zona-sede" className="mb-1.5 block text-sm font-medium text-slate-700">
            Sede
          </label>
          <select id="zona-sede" value={sede} onChange={(event) => setSede(event.target.value)} className={CAMPO}>
            {SEDES.map((opcion) => (
              <option key={opcion} value={opcion}>
                {SEDE_LABELS[opcion]}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="zona-nombre" className="mb-1.5 block text-sm font-medium text-slate-700">
            Nombre
          </label>
          <input
            id="zona-nombre"
            type="text"
            value={nombre}
            onChange={(event) => setNombre(event.target.value)}
            autoComplete="off"
            spellCheck={false}
            placeholder="Ej. BOGOTA"
            className={`${CAMPO} font-mono placeholder:font-sans`}
          />
        </div>

        {error && <p className="text-sm text-estado-terracota-ink">{mensaje(error)}</p>}
      </form>
    </Modal>
  )
}
