import { useState } from 'react'
import type { FormEvent } from 'react'
import { Modal } from '@/components/Modal'
import { ApiError } from '@/lib/api'
import { useActualizarUbicacion, useCrearUbicacion } from './api'
import { CAMPO } from './constantes'
import type { Ubicacion } from './types'

interface UbicacionFormModalProps {
  zonaId: number
  ubicacion: Ubicacion | null
  onCerrar: () => void
}

function mensaje(error: ApiError | null): string | null {
  if (!error) return null
  return Object.values(error.errors).flat()[0] ?? error.message
}

/** Alta o edición de una ubicación bajo una zona. */
export function UbicacionFormModal({ zonaId, ubicacion, onCerrar }: UbicacionFormModalProps) {
  const [nombre, setNombre] = useState(ubicacion?.nombre ?? '')

  const crear = useCrearUbicacion()
  const actualizar = useActualizarUbicacion()
  const error = ubicacion ? actualizar.error : crear.error

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    const nombreLimpio = nombre.trim()
    if (nombreLimpio === '') return

    const payload = { nombre: nombreLimpio }

    if (ubicacion) {
      actualizar.mutate({ id: ubicacion.id, zonaId, ...payload }, { onSuccess: onCerrar })
    } else {
      crear.mutate({ zonaId, ...payload }, { onSuccess: onCerrar })
    }
  }

  const isPending = ubicacion ? actualizar.isPending : crear.isPending

  return (
    <Modal
      titulo={ubicacion ? 'Editar ubicación' : 'Nueva ubicación'}
      onCerrar={onCerrar}
      footer={
        <>
          <button
            type="button"
            onClick={onCerrar}
            disabled={isPending}
            className="flex-1 rounded-md border border-line bg-canvas px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:opacity-40"
          >
            Cancelar
          </button>
          <button
            type="submit"
            form="ubicacion-form"
            disabled={isPending || nombre.trim() === ''}
            className="flex-1 rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {isPending ? 'Guardando…' : 'Guardar'}
          </button>
        </>
      }
    >
      <form id="ubicacion-form" onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label htmlFor="ubicacion-nombre" className="mb-1.5 block text-sm font-medium text-slate-700">
            Nombre
          </label>
          <input
            id="ubicacion-nombre"
            type="text"
            value={nombre}
            onChange={(event) => setNombre(event.target.value)}
            autoComplete="off"
            spellCheck={false}
            placeholder="Ej. SALA_DE_GARANTIA"
            className={`${CAMPO} font-mono placeholder:font-sans`}
          />
        </div>

        {error && <p className="text-sm text-estado-terracota-ink">{mensaje(error)}</p>}
      </form>
    </Modal>
  )
}
