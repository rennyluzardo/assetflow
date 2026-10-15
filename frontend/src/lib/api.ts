import type {
  EquipoRegistro,
  InventarioBusqueda,
  RegistrarEquipoPayload,
  TransicionarEquipoPayload,
} from '@/types/equipos'

const TOKEN_KEY = 'assetflow_token'

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY)
}

export interface ValidationErrors {
  [campo: string]: string[]
}

/** Error tipado para respuestas HTTP/Laravel (incluye errores de validación por campo). */
export class ApiError extends Error {
  readonly status: number
  readonly errors: ValidationErrors

  constructor(message: string, status: number, errors: ValidationErrors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

interface LaravelErrorPayload {
  message?: string
  errors?: ValidationErrors
}

function extraerMensaje(payload: unknown): string {
  if (payload && typeof payload === 'object') {
    const mensaje = (payload as LaravelErrorPayload).message
    if (typeof mensaje === 'string' && mensaje.length > 0) return mensaje
  }
  return 'Error inesperado del servidor.'
}

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const token = getToken()
  const headers = new Headers(init?.headers)

  headers.set('Accept', 'application/json')
  if (init?.body != null) headers.set('Content-Type', 'application/json')
  if (token) headers.set('Authorization', `Bearer ${token}`)

  let response: Response
  try {
    response = await fetch(`/api${path}`, { ...init, headers })
  } catch {
    throw new ApiError('No se pudo conectar con el servidor.', 0)
  }

  const payload: unknown = await response.json().catch(() => null)

  if (!response.ok) {
    const errors = (payload as LaravelErrorPayload | null)?.errors ?? {}
    throw new ApiError(extraerMensaje(payload), response.status, errors)
  }

  return payload as T
}

export const api = {
  async buscarEquipo(placaId: string): Promise<InventarioBusqueda> {
    const res = await request<{ data: InventarioBusqueda }>(
      `/equipos/buscar/${encodeURIComponent(placaId)}`,
    )
    return res.data
  },

  async registrarEquipo(payload: RegistrarEquipoPayload): Promise<EquipoRegistro> {
    const res = await request<{ data: EquipoRegistro }>('/equipos', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    return res.data
  },

  async transicionarEquipo(payload: TransicionarEquipoPayload): Promise<EquipoRegistro> {
    const res = await request<{ data: EquipoRegistro }>('/equipos/transicionar', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    return res.data
  },
}
