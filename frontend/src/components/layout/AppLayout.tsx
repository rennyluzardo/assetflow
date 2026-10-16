import type { ReactNode } from 'react'
import { Link } from '@tanstack/react-router'
import { useAuth } from '@/features/auth/AuthContext'

interface NavItem {
  to: string
  label: string
  permiso: string
}

const NAV_ITEMS: NavItem[] = [
  { to: '/dashboard', label: 'Dashboard', permiso: 'dashboard.ver_solo_propio' },
  { to: '/cargue-masivo', label: 'Cargue Masivo', permiso: 'cargue_masivo.ejecutar' },
  { to: '/inventario', label: 'Inventario', permiso: 'inventario.ver' },
  { to: '/equipos', label: 'Equipos', permiso: 'cpus.ver_bitacora' },
  { to: '/soplado', label: 'Soplado', permiso: 'soplado.ver_bitacora' },
  { to: '/portatiles', label: 'Portátiles', permiso: 'portatiles.ver_bitacora' },
  { to: '/trazabilidad', label: 'Trazabilidad', permiso: 'trazabilidad.ver' },
]

/** Shell minimalista escandinavo: sidebar clara, borde sutil, sin sombras dramáticas. */
export function AppLayout({ children }: { children: ReactNode }) {
  const { session, logout, hasPermission } = useAuth()
  const items = NAV_ITEMS.filter((item) => hasPermission(item.permiso))
  const rol = session?.roles[0] ?? 'analista'

  return (
    <div className="flex min-h-screen bg-canvas text-slate-900">
      <aside className="flex w-60 shrink-0 flex-col border-r border-line bg-surface">
        <div className="flex h-16 items-center border-b border-line px-6">
          <span className="text-lg font-semibold tracking-tight">AssetFlow</span>
        </div>

        <nav className="flex flex-1 flex-col gap-1 p-3">
          {items.map((item) => (
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

        <footer className="border-t border-line p-4">
          {session && (
            <div className="mb-3">
              <p className="truncate text-sm font-medium text-slate-700">{session.user.name}</p>
              <p className="mt-0.5 text-xs capitalize text-slate-400">{rol}</p>
            </div>
          )}
          <button
            type="button"
            onClick={() => void logout()}
            className="w-full rounded-md border border-line bg-canvas px-3 py-1.5 text-left text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900"
          >
            Cerrar sesión
          </button>
          <p className="mt-2 text-[11px] text-slate-400">AssetFlow · ITAM</p>
        </footer>
      </aside>

      <main className="min-w-0 flex-1">{children}</main>
    </div>
  )
}
