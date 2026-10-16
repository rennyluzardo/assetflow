import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { api, clearToken, getToken } from '@/lib/api'
import type { AuthPayload } from './types'

interface AuthContextValue {
  session: AuthPayload | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  hasPermission: (permiso: string) => boolean
}

const AuthContext = createContext<AuthContextValue | null>(null)

/**
 * Guardián de sesión zero-trust: hidrata la identidad desde `GET /api/me`
 * usando el token de Sanctum almacenado en `localStorage`.
 */
export function AuthProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<AuthPayload | null>(null)
  const [loading, setLoading] = useState(true)
  const navigate = useNavigate()

  useEffect(() => {
    let activo = true

    async function hidratar() {
      if (!getToken()) {
        if (activo) setLoading(false)
        return
      }

      try {
        const payload = await api.me()
        if (activo) setSession(payload)
      } catch {
        // Token inválido/revocado: descartarlo y volver al login.
        clearToken()
        if (activo) {
          setSession(null)
          navigate({ to: '/login' })
        }
      } finally {
        if (activo) setLoading(false)
      }
    }

    void hidratar()

    return () => {
      activo = false
    }
  }, [navigate])

  const login = useCallback(async (email: string, password: string) => {
    const res = await api.login(email, password)
    setSession({ user: res.user, roles: res.roles, permissions: res.permissions })
  }, [])

  const logout = useCallback(async () => {
    await api.logout()
    setSession(null)
    navigate({ to: '/login' })
  }, [navigate])

  const hasPermission = useCallback(
    (permiso: string) => session?.permissions.includes(permiso) ?? false,
    [session],
  )

  const value = useMemo(
    () => ({ session, loading, login, logout, hasPermission }),
    [session, loading, login, logout, hasPermission],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth debe usarse dentro de <AuthProvider>.')
  return ctx
}
