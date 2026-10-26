import { useEffect, useState } from 'react'

/** Devuelve el valor pospuesto `delay` ms, útil para no saturar la URL al teclear. */
export function useDebouncedValue<T>(value: T, delay: number): T {
  const [debounced, setDebounced] = useState(value)

  useEffect(() => {
    const id = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(id)
  }, [value, delay])

  return debounced
}
