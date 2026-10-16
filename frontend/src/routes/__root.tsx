import { createRootRoute, Outlet, redirect, useLocation } from '@tanstack/react-router'
import { AppLayout } from '@/components/layout/AppLayout'
import { AuthProvider, useAuth } from '@/features/auth/AuthContext'
import { getToken } from '@/lib/api'

/** Decide si se renderiza el shell autenticado o la pantalla de login aislada. */
function RootShell() {
  const { pathname } = useLocation()
  const { loading } = useAuth()

  if (pathname === '/login') {
    return <Outlet />
  }

  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-canvas text-sm text-slate-500">
        Cargando sesión…
      </div>
    )
  }

  return (
    <AppLayout>
      <Outlet />
    </AppLayout>
  )
}

export const Route = createRootRoute({
  beforeLoad: ({ location }) => {
    // Zero-trust: sin token en localStorage no se entra a ninguna ruta privada.
    if (!getToken() && location.pathname !== '/login') {
      throw redirect({ to: '/login' })
    }
  },
  component: () => (
    <AuthProvider>
      <RootShell />
    </AuthProvider>
  ),
})
