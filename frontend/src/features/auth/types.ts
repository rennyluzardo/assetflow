export interface AuthUser {
  id: number
  name: string
  email: string
  sede?: string | null
}

/** Sesión autenticada devuelta por `GET /api/me` (y embebida en el login sin el token). */
export interface AuthPayload {
  user: AuthUser
  roles: string[]
  permissions: string[]
}

/** Respuesta de `POST /api/login`: incluye el token plano de Sanctum. */
export interface LoginResponse extends AuthPayload {
  token: string
}
