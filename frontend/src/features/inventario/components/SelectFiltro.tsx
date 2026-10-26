import type { ReactNode } from 'react'

/** Select uniforme de la barra de filtros. */
export function SelectFiltro({
  valor,
  etiqueta,
  onChange,
  children,
}: {
  valor: string | number
  etiqueta: string
  onChange: (valor: string) => void
  children: ReactNode
}) {
  return (
    <select
      value={valor}
      onChange={(event) => onChange(event.target.value)}
      aria-label={etiqueta}
      className="rounded-md border border-line bg-canvas px-3 py-2 text-sm text-slate-700 outline-none transition-colors focus:border-slate-400"
    >
      {children}
    </select>
  )
}
