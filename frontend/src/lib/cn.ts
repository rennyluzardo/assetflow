/**
 * Combina clases de Tailwind descartando valores vacíos.
 * Alternativa mínima a `clsx` + `tailwind-merge` sin dependencias externas.
 */
export function cn(...classes: Array<string | false | null | undefined>): string {
  return classes.filter(Boolean).join(' ')
}
