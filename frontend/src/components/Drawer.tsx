import { createContext, useContext, useEffect, useState } from 'react'
import type { ReactNode } from 'react'
import { createPortal } from 'react-dom'

interface DrawerProps {
  titulo: string
  onCerrar: () => void
  children: ReactNode
  footer?: ReactNode
}

/** Destino del footer fijo: los paneles portan ahí sus acciones sin salir del scroll. */
const FooterTargetContext = createContext<HTMLElement | null>(null)

/**
 * Panel lateral derecho (slide-over) que preserva el contexto del listado
 * maestro: el detalle convive con la tabla de fondo en lugar de reemplazarla.
 */
export function Drawer({ titulo, onCerrar, children, footer }: DrawerProps) {
  const [footerTarget, setFooterTarget] = useState<HTMLElement | null>(null)

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
        className="absolute inset-y-0 right-0 flex h-full w-full max-w-md flex-col overflow-hidden border-l border-line bg-surface shadow-2xl"
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

        {/* Sin padding vertical: así `sticky top-0` ancla la cinta al borde
            visible del scrollport en lugar de dejar una franja descubierta. */}
        <div className="min-h-0 flex-1 overflow-y-auto px-5">
          <FooterTargetContext.Provider value={footerTarget}>{children}</FooterTargetContext.Provider>
        </div>

        <footer
          ref={setFooterTarget}
          className="shrink-0 border-t border-slate-200 bg-white p-4 empty:hidden"
        >
          {footer}
        </footer>
      </aside>
    </div>
  )
}

/**
 * Porta las acciones al footer fijo del Drawer: quedan siempre visibles al
 * fondo mientras el cuerpo (cabecera, escáner y listado) hace scroll aparte.
 */
export function DrawerFooter({ children }: { children: ReactNode }) {
  const target = useContext(FooterTargetContext)
  return target ? createPortal(children, target) : null
}
