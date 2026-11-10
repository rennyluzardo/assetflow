import { useEffect } from 'react'
import type { ReactNode } from 'react'

interface ModalProps {
  titulo: string
  descripcion?: string
  onCerrar: () => void
  children: ReactNode
  footer?: ReactNode
}

/**
 * Modal central global (figura-fondo): oscurece el lienzo y centra el diálogo.
 * Se monta/desmonta desde el padre, por lo que el contenido reinicia su estado
 * en cada apertura.
 */
export function Modal({ titulo, descripcion, onCerrar, children, footer }: ModalProps) {
  useEffect(() => {
    const handler = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onCerrar()
    }
    document.addEventListener('keydown', handler)
    return () => document.removeEventListener('keydown', handler)
  }, [onCerrar])

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
      onClick={onCerrar}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-label={titulo}
        className="w-full max-w-lg rounded-lg border border-line bg-surface p-5"
        onClick={(event) => event.stopPropagation()}
      >
        <header className="mb-4 flex items-start justify-between gap-4">
          <div>
            <h2 className="text-lg font-semibold tracking-tight text-slate-900">{titulo}</h2>
            {descripcion && <p className="mt-1 text-sm text-slate-500">{descripcion}</p>}
          </div>
          <button
            type="button"
            onClick={onCerrar}
            aria-label="Cerrar"
            className="rounded-md p-1 text-slate-400 transition-colors hover:bg-muted hover:text-slate-700"
          >
            <svg viewBox="0 0 20 20" fill="currentColor" className="h-5 w-5" aria-hidden="true">
              <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
            </svg>
          </button>
        </header>

        {children}

        {footer && <footer className="mt-6 flex gap-3">{footer}</footer>}
      </div>
    </div>
  )
}
