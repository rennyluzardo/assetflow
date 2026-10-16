/** Cuerpo multipart de `POST /api/cargue-masivo`. */
export interface ProcesarCarguePayload {
  num_traslado: string
  archivo: File
}

/** Error de fila reportado por el backend durante la ingesta. */
export interface CargueErrorFila {
  fila: number
  error: string
}

/** Resultado de `POST /api/cargue-masivo`. */
export interface CargueResultado {
  num_traslado: string
  insertados: number
  omitidos: number
  errores: CargueErrorFila[]
}
