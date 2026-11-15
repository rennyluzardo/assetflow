export const SEDE_LABELS: Record<string, string> = {
  COLOMBIA: 'Colombia',
  GUYANA: 'Guyana',
}

export const SEDES = ['COLOMBIA', 'GUYANA'] as const

/** Clase base compartida por inputs y selects del formulario nórdico. */
export const CAMPO =
  'w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-300'
