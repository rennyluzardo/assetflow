import { cn } from '@/lib/cn'
import { CAMPO, ZONAS, ZONA_LABELS } from './constantes'
import type { ZonaSede } from './types'

interface CampoTextoProps {
  id: string
  label: string
  value: string
  onChange: (value: string) => void
  placeholder?: string
  disabled?: boolean
  mono?: boolean
}

/** Campo de texto del formulario nórdico (etiqueta + input). */
export function CampoTexto({
  id,
  label,
  value,
  onChange,
  placeholder,
  disabled,
  mono,
}: CampoTextoProps) {
  return (
    <div>
      <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-slate-700">
        {label}
      </label>
      <input
        id={id}
        type="text"
        value={value}
        onChange={(event) => onChange(event.target.value)}
        disabled={disabled}
        placeholder={placeholder}
        autoComplete="off"
        spellCheck={false}
        className={cn(CAMPO, mono && 'font-mono placeholder:font-sans')}
      />
    </div>
  )
}

interface CampoZonaProps {
  id: string
  label: string
  value: ZonaSede
  onChange: (value: ZonaSede) => void
  disabled?: boolean
}

/** Selector de sede (origen/destino) con las opciones y etiquetas del módulo. */
export function CampoZona({ id, label, value, onChange, disabled }: CampoZonaProps) {
  return (
    <div>
      <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-slate-700">
        {label}
      </label>
      <select
        id={id}
        value={value}
        onChange={(event) => onChange(event.target.value as ZonaSede)}
        disabled={disabled}
        className={CAMPO}
      >
        {ZONAS.map((zona) => (
          <option key={zona} value={zona}>
            {ZONA_LABELS[zona]}
          </option>
        ))}
      </select>
    </div>
  )
}
