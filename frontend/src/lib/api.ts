import type {
  EquipoRegistro,
  InventarioBusqueda,
  RegistrarEquipoPayload,
  TransicionarEquipoPayload,
} from '@/types/equipos'
import type { AuthPayload, LoginResponse } from '@/features/auth/types'
import type { RegistrarSopladoPayload, SopladoRegistroResult } from '@/features/soplado/types'
import type { InventarioParams, InventarioResponse } from '@/features/inventario/types'
import type { CargueResultado, ProcesarCarguePayload } from '@/features/cargue-masivo/types'

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

/**
 * Añade un booleano a un FormData en el formato que acepta la regla `boolean`
 * de Laravel ("1"/"0"); `String(true)` enviaría "true" y fallaría la validación.
 */
function appendBoolean(form: FormData, campo: string, valor: boolean): void {
  form.append(campo, valor ? '1' : '0')
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
  const esFormData = init?.body instanceof FormData
  if (init?.body != null && !esFormData) headers.set('Content-Type', 'application/json')
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
  async login(email: string, password: string): Promise<LoginResponse> {
    const res = await request<LoginResponse>('/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    })
    setToken(res.token)
    return res
  },

  async logout(): Promise<void> {
    try {
      await request<{ message: string }>('/logout', { method: 'POST' })
    } finally {
      clearToken()
    }
  },

  async me(): Promise<AuthPayload> {
    return request<AuthPayload>('/me')
  },

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

  async buscarSoplado(placaId: string): Promise<InventarioBusqueda> {
    const res = await request<{ data: InventarioBusqueda }>(
      `/soplado/buscar/${encodeURIComponent(placaId)}`,
    )
    return res.data
  },

  async registrarSoplado(payload: RegistrarSopladoPayload): Promise<SopladoRegistroResult> {
    const form = new FormData()
    form.append('placa_id', payload.placa_id)
    appendBoolean(form, 'energiza', payload.energiza)
    appendBoolean(form, 'da_video', payload.da_video)
    appendBoolean(form, 'acceso_bios', payload.acceso_bios)
    form.append('tipo_suciedad', payload.tipo_suciedad)
    appendBoolean(form, 'aplica_pasta_termica', payload.aplica_pasta_termica)
    form.append('foto_ruta', payload.foto_ruta)
    form.append('ubicacion_destino', payload.ubicacion_destino)

    const res = await request<{ data: SopladoRegistroResult }>('/soplado', {
      method: 'POST',
      body: form,
    })
    return res.data
  },

  async listarInventario(params: InventarioParams): Promise<InventarioResponse> {
    const query = new URLSearchParams()
    if (params.page) query.set('page', String(params.page))
    if (params.per_page) query.set('per_page', String(params.per_page))
    if (params.search) query.set('search', params.search)
    if (params.num_traslado) query.set('num_traslado', params.num_traslado)
    if (params.estado_actual) query.set('estado_actual', params.estado_actual)

    const queryString = query.toString()
    return request<InventarioResponse>(`/inventario${queryString ? `?${queryString}` : ''}`)
  },

  async procesarCargueMasivo(payload: ProcesarCarguePayload): Promise<CargueResultado> {
    const form = new FormData()
    form.append('num_traslado', payload.num_traslado)
    form.append('archivo', payload.archivo)

    const res = await request<{ data: CargueResultado }>('/cargue-masivo', {
      method: 'POST',
      body: form,
    })
    return res.data
  },
}
