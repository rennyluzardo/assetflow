import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { ApiError } from '@/lib/api'
import { useAuth } from './AuthContext'

const DEV_USERS = [
  { email: 'admin@assetflow.local', label: 'Administrador' },
  { email: 'cpu@assetflow.local', label: 'Técnico CPU' },
  { email: 'soplado@assetflow.local', label: 'Técnico Soplado' },
] as const

/** Pantalla de acceso con estética escandinava y accesos rápidos de desarrollo. */
export function LoginScreen() {
  const { session, login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [enviando, setEnviando] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (session) void navigate({ to: '/dashboard' })
  }, [session, navigate])

  const autenticar = async (mail: string, pass: string) => {
    setError(null)
    setEnviando(true)
    try {
      await login(mail, pass)
    } catch (err) {
      setError(err instanceof ApiError ? err.message : 'No se pudo iniciar sesión.')
    } finally {
      setEnviando(false)
    }
  }

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    void autenticar(email, password)
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-canvas px-4">
      <div className="w-full max-w-sm">
        <div className="rounded-lg border border-line bg-surface p-6">
          <div className="mb-6">
            <h1 className="text-xl font-semibold tracking-tight text-slate-900">AssetFlow</h1>
            <p className="mt-1 text-sm text-slate-500">Inicia sesión para continuar.</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="email" className="mb-1.5 block text-sm font-medium text-slate-700">
                Correo corporativo
              </label>
              <input
                id="email"
                type="email"
                autoComplete="username"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                placeholder="usuario@assetflow.local"
                className="w-full rounded-md border border-line bg-surface px-3 py-2.5 text-sm text-slate-900 outline-none transition-colors focus:border-slate-400 focus:ring-2 focus:ring-slate-300"
              />
            </div>

            <div>
              <label htmlFor="password" className="mb-1.5 block text-sm font-medium text-slate-700">
                Contraseña
              </label>
              <input
                id="password"
                type="password"
                autoComplete="current-password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                placeholder="••••••••"
                className="w-full rounded-md border border-line bg-surface px-3 py-2.5 text-sm text-slate-900 outline-none transition-colors focus:border-slate-400 focus:ring-2 focus:ring-slate-300"
              />
            </div>

            {error && <p className="text-sm text-estado-terracota-ink">{error}</p>}

            <button
              type="submit"
              disabled={enviando}
              className="w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
            >
              {enviando ? 'Ingresando…' : 'Ingresar'}
            </button>
          </form>
        </div>

        {import.meta.env.DEV && (
          <div className="mt-4 rounded-lg border border-line bg-surface p-4">
            <p className="text-xs font-medium uppercase tracking-wide text-slate-400">
              Acceso rápido (solo desarrollo)
            </p>
            <div className="mt-2 grid grid-cols-1 gap-2">
              {DEV_USERS.map((usuario) => (
                <button
                  key={usuario.email}
                  type="button"
                  disabled={enviando}
                  onClick={() => void autenticar(usuario.email, 'password')}
                  className="rounded-md border border-line bg-canvas px-3 py-2 text-left text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:bg-muted hover:text-slate-900 disabled:opacity-40"
                >
                  {usuario.label}
                  <span className="block font-mono text-xs text-slate-400">{usuario.email}</span>
                </button>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
