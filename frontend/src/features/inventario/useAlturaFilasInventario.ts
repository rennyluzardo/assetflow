import { useLayoutEffect, useRef, useState } from 'react'

/** Altura mínima de fila: evita comprimir cuando hay más filas de las que caben (se mantiene el scroll). */
const ALTURA_MIN = 40
/** Altura máxima de fila: evita filas absurdamente altas cuando un filtro devuelve pocos resultados. */
const ALTURA_MAX = 96

/**
 * Reparte el alto disponible del contenedor de scroll entre las filas para que
 * llenen la tabla al 100% (sin hueco debajo). Recalcula con `ResizeObserver`.
 */
export function useAlturaFilasInventario(cantidadFilas: number) {
  const ref = useRef<HTMLDivElement>(null)
  const [altura, setAltura] = useState<number | undefined>(undefined)

  useLayoutEffect(() => {
    const contenedor = ref.current

    if (!contenedor || cantidadFilas === 0) {
      setAltura(undefined)
      return
    }

    const calcular = () => {
      const altoThead = contenedor.querySelector('thead')?.getBoundingClientRect().height ?? 0
      const disponible = contenedor.clientHeight - altoThead
      const porFila = Math.floor(disponible / cantidadFilas)
      setAltura(Math.min(ALTURA_MAX, Math.max(ALTURA_MIN, porFila)))
    }

    calcular()
    const observador = new ResizeObserver(calcular)
    observador.observe(contenedor)

    return () => observador.disconnect()
  }, [cantidadFilas])

  return { ref, altura }
}
