import { useEffect } from 'react'
import type { ReactNode } from 'react'

interface DrawerProps {
  titulo: string
  onCerrar: () => void
  children: ReactNode
  footer?: ReactNode
}

/**
 * Panel lateral derecho (slide-over) que preserva el contexto del listado
 * maestro: el detalle convive con la tabla de fondo en lugar de reemplazarla.
 */
export function Drawer({ titulo, onCerrar, children, footer }: DrawerProps) {
  useEffect(() => {
    const handler = (event: KeyboardEvent) => {
      if (event.key === 'Escape') onCerrar()
    }
    document.addEventListener('keydown', handler)
    return () => document.removeEventListener('keydown', handler)
  }, [onCerrar])

  return (
    <div className="fixed inset-0 z-50">
      <div className="absolute inset-0 bg-slate-900/40" onClick={onCerrar} aria-hidden="true" />

      <aside
        role="dialog"
        aria-modal="true"
        aria-label={titulo}
        className="absolute inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-line bg-surface shadow-2xl"
      >
        <header className="flex items-center justify-between gap-4 border-b border-line px-5 py-4">
          <h2 className="truncate text-lg font-semibold tracking-tight text-slate-900">{titulo}</h2>
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

        <div className="min-h-0 flex-1 overflow-y-auto px-5 py-4">{children}</div>

        {footer && <footer className="border-t border-line px-5 py-4">{footer}</footer>}
      </aside>
    </div>
  )
}
