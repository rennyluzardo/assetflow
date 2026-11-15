import type {
  EquipoRegistro,
  InventarioBusqueda,
  RegistrarEquipoPayload,
  TransicionarEquipoPayload,
} from '@/types/equipos'
import type { AuthPayload, LoginResponse } from '@/features/auth/types'
import type { RegistrarSopladoPayload, SopladoRegistroResult } from '@/features/soplado/types'
import type { InventarioParams, InventarioResponse } from '@/features/inventario/types'
import type {
  CerrarPortatilPayload,
  PortatilBusqueda,
  PortatilRegistro,
  RegistrarPortatilPayload,
} from '@/features/portatiles/types'
import type { TrazabilidadResponse } from '@/features/trazabilidad/types'
import type { DashboardParams, DashboardResponse } from '@/features/dashboard/types'
import type {
  BusquedaEquipoTraslado,
  ImportacionResultado,
  PivoteEquipo,
  RegistrarTrasladoPayload,
  Traslado,
  TrasladoDetalle,
  TrasladoListaParams,
  TrasladoListaResponse,
  Ubicacion,
  Zona,
} from '@/features/traslados/types'

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
    const form = new FormData()
    form.append('placa_id', payload.placa_id)
    form.append('tipo_gestion', payload.tipo_gestion)
    if (payload.requiere_soplado_o_pasta !== undefined) {
      appendBoolean(form, 'requiere_soplado_o_pasta', payload.requiere_soplado_o_pasta)
    }
    if (payload.esta_funcional !== undefined) appendBoolean(form, 'esta_funcional', payload.esta_funcional)
    if (payload.tiene_garantia !== undefined) appendBoolean(form, 'tiene_garantia', payload.tiene_garantia)
    if (payload.hay_pieza !== undefined) appendBoolean(form, 'hay_pieza', payload.hay_pieza)
    if (payload.motivo_baja) form.append('motivo_baja', payload.motivo_baja)
    if (payload.serial_disco) form.append('serial_disco', payload.serial_disco)
    if (payload.ticket_garantia) form.append('ticket_garantia', payload.ticket_garantia)
    if (payload.pieza_garantia) form.append('pieza_garantia', payload.pieza_garantia)
    form.append('ubicacion_destino', payload.ubicacion_destino)
    if (payload.observaciones) form.append('observaciones', payload.observaciones)
    if (payload.foto_equipo) form.append('foto_equipo', payload.foto_equipo)

    const res = await request<{ data: EquipoRegistro }>('/equipos', {
      method: 'POST',
      body: form,
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
    if (params.sort) {
      query.set('sort', params.sort)
      if (params.direction) query.set('direction', params.direction)
    }

    const queryString = query.toString()
    return request<InventarioResponse>(`/inventario${queryString ? `?${queryString}` : ''}`)
  },

  async buscarPortatil(placaId: string): Promise<PortatilBusqueda> {
    const res = await request<{ data: PortatilBusqueda }>(
      `/portatiles/buscar/${encodeURIComponent(placaId)}`,
    )
    return res.data
  },

  async registrarPortatil(payload: RegistrarPortatilPayload): Promise<PortatilRegistro> {
    const res = await request<{ data: PortatilRegistro }>('/portatiles', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    return res.data
  },

  async cerrarPortatil(id: number, payload: CerrarPortatilPayload): Promise<PortatilRegistro> {
    const res = await request<{ data: PortatilRegistro }>(`/portatiles/${id}/cerrar`, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    })
    return res.data
  },

  async subirFotoPortatil(id: number, file: File): Promise<PortatilRegistro> {
    const form = new FormData()
    form.append('foto_evidencia', file)

    const res = await request<{ data: PortatilRegistro }>(`/portatiles/${id}/foto`, {
      method: 'POST',
      body: form,
    })
    return res.data
  },

  async buscarTrazabilidad(q: string): Promise<TrazabilidadResponse> {
    const query = new URLSearchParams({ q })
    return request<TrazabilidadResponse>(`/trazabilidad?${query.toString()}`)
  },

  async dashboard(params: DashboardParams): Promise<DashboardResponse> {
    const query = new URLSearchParams()
    if (params.periodo) query.set('periodo', params.periodo)
    if (params.modulo) query.set('modulo', params.modulo)
    if (params.tipo_equipo) query.set('tipo_equipo', params.tipo_equipo)
    if (params.estado) query.set('estado', params.estado)
    if (params.fecha_inicio) query.set('fecha_inicio', params.fecha_inicio)
    if (params.fecha_fin) query.set('fecha_fin', params.fecha_fin)

    const queryString = query.toString()
    const res = await request<{ data: DashboardResponse }>(
      `/dashboard${queryString ? `?${queryString}` : ''}`,
    )
    return res.data
  },

  async listarTraslados(params: TrasladoListaParams = {}): Promise<TrasladoListaResponse> {
    const query = new URLSearchParams()
    if (params.page) query.set('page', String(params.page))
    if (params.per_page) query.set('per_page', String(params.per_page))
    if (params.codigo) query.set('codigo', params.codigo)
    if (params.zona_origen) query.set('zona_origen', params.zona_origen)
    if (params.ubicacion_origen) query.set('ubicacion_origen', params.ubicacion_origen)
    if (params.zona_destino) query.set('zona_destino', params.zona_destino)
    if (params.ubicacion_destino) query.set('ubicacion_destino', params.ubicacion_destino)
    if (params.estado) query.set('estado', params.estado)

    const queryString = query.toString()
    return request<TrasladoListaResponse>(`/traslados${queryString ? `?${queryString}` : ''}`)
  },

  async registrarTraslado(payload: RegistrarTrasladoPayload): Promise<Traslado> {
    const res = await request<{ data: Traslado }>('/traslados', {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    return res.data
  },

  async detalleTraslado(id: number): Promise<TrasladoDetalle> {
    const res = await request<{ data: TrasladoDetalle }>(`/traslados/${id}`)
    return res.data
  },

  async buscarEquipoTraslado(placaId: string): Promise<BusquedaEquipoTraslado> {
    const res = await request<{ data: BusquedaEquipoTraslado }>(
      `/traslados/buscar/${encodeURIComponent(placaId)}`,
    )
    return res.data
  },

  async agregarEquipoTraslado(id: number, placaId: string): Promise<PivoteEquipo> {
    const res = await request<{ data: PivoteEquipo }>(`/traslados/${id}/equipos`, {
      method: 'POST',
      body: JSON.stringify({ placa_id: placaId }),
    })
    return res.data
  },

  async importarEquiposTraslado(id: number, archivo: File): Promise<ImportacionResultado> {
    const form = new FormData()
    form.append('archivo', archivo)

    const res = await request<{ data: ImportacionResultado }>(`/traslados/${id}/equipos/importar`, {
      method: 'POST',
      body: form,
    })
    return res.data
  },

  async quitarEquipoTraslado(id: number, equipoId: number): Promise<void> {
    await request<{ data: null }>(`/traslados/${id}/equipos/${equipoId}`, { method: 'DELETE' })
  },

  async despacharTraslado(id: number): Promise<Traslado> {
    const res = await request<{ data: Traslado }>(`/traslados/${id}/despachar`, { method: 'POST' })
    return res.data
  },

  async verificarEquipoTraslado(id: number, placaId: string): Promise<PivoteEquipo> {
    const res = await request<{ data: PivoteEquipo }>(`/traslados/${id}/equipos/verificar`, {
      method: 'POST',
      body: JSON.stringify({ placa_id: placaId }),
    })
    return res.data
  },

  async recibirTraslado(id: number): Promise<Traslado> {
    const res = await request<{ data: Traslado }>(`/traslados/${id}/recibir`, { method: 'POST' })
    return res.data
  },

  async revertirTrasladoApertura(id: number): Promise<Traslado> {
    const res = await request<{ data: Traslado }>(`/traslados/${id}/revertir-apertura`, {
      method: 'POST',
    })
    return res.data
  },

  async zonasActivas(): Promise<Zona[]> {
    const res = await request<{ data: Zona[] }>('/zonas')
    return res.data
  },

  async zonasGestion(): Promise<Zona[]> {
    const res = await request<{ data: Zona[] }>('/zonas-ubicaciones')
    return res.data
  },

  async crearZona(payload: { sede: string; nombre: string }): Promise<Zona> {
    const res = await request<{ data: Zona }>('/zonas', { method: 'POST', body: JSON.stringify(payload) })
    return res.data
  },

  async actualizarZona(id: number, payload: { sede: string; nombre: string }): Promise<Zona> {
    const res = await request<{ data: Zona }>(`/zonas/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    return res.data
  },

  async activarZona(id: number): Promise<Zona> {
    const res = await request<{ data: Zona }>(`/zonas/${id}/activar`, { method: 'PATCH' })
    return res.data
  },

  async inactivarZona(id: number): Promise<Zona> {
    const res = await request<{ data: Zona }>(`/zonas/${id}/inactivar`, { method: 'PATCH' })
    return res.data
  },

  async crearUbicacion(zonaId: number, payload: { nombre: string }): Promise<Ubicacion> {
    const res = await request<{ data: Ubicacion }>(`/zonas/${zonaId}/ubicaciones`, {
      method: 'POST',
      body: JSON.stringify(payload),
    })
    return res.data
  },

  async actualizarUbicacion(
    id: number,
    payload: { zona_id: number; nombre: string },
  ): Promise<Ubicacion> {
    const res = await request<{ data: Ubicacion }>(`/ubicaciones/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    })
    return res.data
  },

  async activarUbicacion(id: number): Promise<Ubicacion> {
    const res = await request<{ data: Ubicacion }>(`/ubicaciones/${id}/activar`, { method: 'PATCH' })
    return res.data
  },

  async inactivarUbicacion(id: number): Promise<Ubicacion> {
    const res = await request<{ data: Ubicacion }>(`/ubicaciones/${id}/inactivar`, { method: 'PATCH' })
    return res.data
  },
}
