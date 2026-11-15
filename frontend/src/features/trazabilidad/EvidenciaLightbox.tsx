import { useEffect } from 'react'
import { descargarEvidencia } from './fotos'

interface EvidenciaLightboxProps {
  url: string
  alt: string
  onCerrar: () => void
}

/** Visor maximizado de una evidencia fotográfica (cierre con Escape o backdrop). */
export function EvidenciaLightbox({ url, alt, onCerrar }: EvidenciaLightboxProps) {
  useEffect(() => {
    const handler = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onCerrar()
    }
    document.addEventListener('keydown', handler)
    return () => document.removeEventListener('keydown', handler)
  }, [onCerrar])

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-label="Visor de evidencia fotográfica"
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
      onClick={onCerrar}
    >
      <button
        type="button"
        onClick={onCerrar}
        aria-label="Cerrar visor"
        className="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white transition-colors hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
      >
        <svg viewBox="0 0 20 20" fill="currentColor" className="h-5 w-5" aria-hidden="true">
          <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
        </svg>
      </button>

      <div className="flex flex-col items-center gap-4" onClick={(event) => event.stopPropagation()}>
        <img
          src={url}
          alt={alt}
          className="max-h-[85vh] max-w-[90vw] rounded-lg object-contain shadow-2xl"
        />
        <button
          type="button"
          onClick={() => void descargarEvidencia(url)}
          className="inline-flex items-center gap-2 rounded-lg border border-white/40 bg-white/95 px-3 py-2 text-sm font-medium text-slate-900 shadow-lg transition-colors hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
        >
          <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4" aria-hidden="true">
            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" />
            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" />
          </svg>
          Descargar
        </button>
      </div>
    </div>
  )
}
