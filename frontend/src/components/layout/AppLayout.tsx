import type { ReactNode } from 'react'
import { Link } from '@tanstack/react-router'

interface NavItem {
  to: string
  label: string
}

const NAV_ITEMS: NavItem[] = [
  { to: '/dashboard', label: 'Dashboard' },
  { to: '/cargue-masivo', label: 'Cargue Masivo' },
  { to: '/inventario', label: 'Inventario' },
  { to: '/equipos', label: 'Equipos' },
  { to: '/soplado', label: 'Soplado' },
  { to: '/portatiles', label: 'Portátiles' },
  { to: '/trazabilidad', label: 'Trazabilidad' },
]

/** Shell minimalista escandinavo: sidebar clara, borde sutil, sin sombras dramáticas. */
export function AppLayout({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-screen bg-canvas text-slate-900">
      <aside className="flex w-60 shrink-0 flex-col border-r border-line bg-surface">
        <div className="flex h-16 items-center border-b border-line px-6">
          <span className="text-lg font-semibold tracking-tight">AssetFlow</span>
        </div>

        <nav className="flex flex-1 flex-col gap-1 p-3">
          {NAV_ITEMS.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              className="rounded-md px-3 py-2 text-sm font-medium transition-colors"
              activeProps={{ className: 'bg-muted text-slate-900' }}
              inactiveProps={{ className: 'text-slate-600 hover:bg-muted hover:text-slate-900' }}
            >
              {item.label}
            </Link>
          ))}
        </nav>

        <footer className="border-t border-line p-4 text-xs text-slate-400">AssetFlow · ITAM</footer>
      </aside>

      <main className="min-w-0 flex-1">{children}</main>
    </div>
  )
}
