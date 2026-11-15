import { cn } from '@/lib/cn'
import { CAMPO } from './constantes'
import type { Ubicacion, Zona } from './types'

interface CampoTextoProps {
  id: string
  label: string
  value: string
  onChange: (value: string) => void
  placeholder?: string
  disabled?: boolean
  mono?: boolean
}

export function CampoTexto({ id, label, value, onChange, placeholder, disabled, mono }: CampoTextoProps) {
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

interface CampoTextareaProps {
  id: string
  label: string
  value: string
  onChange: (value: string) => void
  placeholder?: string
  disabled?: boolean
}

export function CampoTextarea({ id, label, value, onChange, placeholder, disabled }: CampoTextareaProps) {
  return (
    <div>
      <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-slate-700">
        {label}
      </label>
      <textarea
        id={id}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        disabled={disabled}
        placeholder={placeholder}
        rows={3}
        className={cn(CAMPO, 'resize-none')}
      />
    </div>
  )
}

interface SelectorZonaProps {
  id: string
  label: string
  zonas: Zona[]
  value: string
  onChange: (zona: Zona | undefined) => void
  disabled?: boolean
}

/** Selector de zona destino alimentado por el maestro `/api/zonas` (activas). */
export function SelectorZona({ id, label, zonas, value, onChange, disabled }: SelectorZonaProps) {
  const seleccionada = zonas.find((zona) => zona.nombre === value)

  return (
    <div>
      <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-slate-700">
        {label}
      </label>
      <select
        id={id}
        value={value}
        disabled={disabled}
        onChange={(event) => onChange(zonas.find((zona) => zona.nombre === event.target.value))}
        className={CAMPO}
      >
        <option value="">Seleccione una zona…</option>
        {zonas.map((zona) => (
          <option key={zona.id} value={zona.nombre}>
            {zona.nombre} ({zona.sede})
          </option>
        ))}
      </select>
      {seleccionada && seleccionada.sede !== 'COLOMBIA' && (
        <p className="mt-1 text-xs text-slate-400">Sede {seleccionada.sede}</p>
      )}
    </div>
  )
}

interface SelectorUbicacionProps {
  id: string
  label: string
  ubicaciones: Ubicacion[]
  value: string
  onChange: (nombre: string) => void
  disabled?: boolean
}

/** Selector de ubicación destino (dentro de la zona seleccionada). */
export function SelectorUbicacion({ id, label, ubicaciones, value, onChange, disabled }: SelectorUbicacionProps) {
  return (
    <div>
      <label htmlFor={id} className="mb-1.5 block text-sm font-medium text-slate-700">
        {label}
      </label>
      <select
        id={id}
        value={value}
        disabled={disabled || ubicaciones.length === 0}
        onChange={(event) => onChange(event.target.value)}
        className={CAMPO}
      >
        <option value="">
          {ubicaciones.length === 0 ? 'Seleccione una zona primero' : 'Seleccione una ubicación…'}
        </option>
        {ubicaciones.map((ubicacion) => (
          <option key={ubicacion.id} value={ubicacion.nombre}>
            {ubicacion.nombre}
          </option>
        ))}
      </select>
    </div>
  )
}
