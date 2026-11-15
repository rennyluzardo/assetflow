import { useState } from 'react'
import { EvidenciaLightbox } from './EvidenciaLightbox'
import { normalizarFotos } from './fotos'
import type { EventoTrazabilidad } from './types'

/**
 * Grilla de miniaturas de evidencia del hito. No renderiza ningún bloque
 * (ni mensajes vacíos) cuando el registro no tiene fotografías.
 */
export function EvidenciaFotos({ evento }: { evento: EventoTrazabilidad }) {
  const fotos = normalizarFotos(evento)
  const [indiceActivo, setIndiceActivo] = useState<number | null>(null)

  if (fotos.length === 0) return null

  const fotoActiva = indiceActivo === null ? undefined : fotos[indiceActivo]

  return (
    <>
      <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
        {fotos.map((url, indice) => (
          <button
            key={`${url}-${indice}`}
            type="button"
            onClick={() => setIndiceActivo(indice)}
            aria-label={`Ampliar evidencia fotográfica ${indice + 1}`}
            className="group h-24 w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100 transition-colors hover:border-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
          >
            <img
              src={url}
              alt={`Evidencia fotográfica ${indice + 1} de ${evento.estacion}`}
              loading="lazy"
              className="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
            />
          </button>
        ))}
      </div>

      {fotoActiva && (
        <EvidenciaLightbox
          url={fotoActiva}
          alt={`Evidencia fotográfica de ${evento.estacion}`}
          onCerrar={() => setIndiceActivo(null)}
        />
      )}
    </>
  )
}
