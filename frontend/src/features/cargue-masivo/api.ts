import { useMutation } from '@tanstack/react-query'
import { api, ApiError } from '@/lib/api'
import type { CargueResultado, ProcesarCarguePayload } from './types'

/** Procesamiento de un lote de ingesta masiva desde `/cargue-masivo`. */
export function useProcesarCargueMasivo() {
  return useMutation<CargueResultado, ApiError, ProcesarCarguePayload>({
    mutationFn: (payload) => api.procesarCargueMasivo(payload),
  })
}
