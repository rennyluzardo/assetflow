import { useId } from 'react'
import type { FocusEvent, KeyboardEvent } from 'react'
import { cn } from '@/lib/cn'

/**
 * Estado del escaneo. `warning` cubre el caso "abierto / en tránsito" (ámbar)
 * y `error` cubre "no encontrado / ya cerrado" (terracota).
 */
export type ScannerStatus = 'idle' | 'loading' | 'success' | 'warning' | 'error'

export interface ScannerInputProps {
  value: string
  onChange: (value: string) => void
  onSubmit: (value: string) => void
  status?: ScannerStatus
  message?: string | null
  label?: string
  placeholder?: string
  disabled?: boolean
  autoFocus?: boolean
  id?: string
  'data-testid'?: string
}

function bordePorEstado(status: ScannerStatus): string {
  switch (status) {
    case 'success':
      return 'border-estado-verde-line'
    case 'warning':
      return 'border-estado-ambar-line'
    case 'error':
      return 'border-estado-terracota-line'
    case 'loading':
      return 'border-estado-ambar-line'
    default:
      return 'border-line'
  }
}

function anilloPorEstado(status: ScannerStatus): string {
  switch (status) {
    case 'success':
      return 'focus:ring-estado-verde-line'
    case 'warning':
      return 'focus:ring-estado-ambar-line'
    case 'error':
      return 'focus:ring-estado-terracota-line'
    case 'loading':
      return 'focus:ring-estado-ambar-line'
    default:
      return 'focus:ring-slate-300'
  }
}

function EstadoAlerta({ status, message }: { status: ScannerStatus; message?: string | null }) {
  if (status === 'idle') return null

  const porEstado = {
    loading: { texto: message ?? 'Consultando inventario…', className: 'text-estado-ambar-ink' },
    success: { texto: message ?? 'Equipo válido — listo para procesar', className: 'text-estado-verde-ink' },
    warning: { texto: message ?? 'Atención requerida', className: 'text-estado-ambar-ink' },
    error: { texto: message ?? 'No se encontró el equipo', className: 'text-estado-terracota-ink' },
  } as const
  const config = porEstado[status]

  return (
    <p
      role="status"
      aria-live="polite"
      data-testid="scanner-status"
      data-status={status}
      className={cn('mt-1.5 flex items-start gap-1.5 text-sm', config.className)}
    >
      {status === 'success' && (
        <svg viewBox="0 0 20 20" fill="currentColor" className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true">
          <path
            fillRule="evenodd"
            d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
            clipRule="evenodd"
          />
        </svg>
      )}
      <span>{config.texto}</span>
    </p>
  )
}

/**
 * Input ergonómico para pistola lectora de códigos de barras:
 * `autoFocus`, selecciona su contenido al recibir foco y dispara `onSubmit`
 * al detectar `Enter` (comportamiento nativo de la pistola) sin recargar la página.
 */
export function ScannerInput({
  value,
  onChange,
  onSubmit,
  status = 'idle',
  message,
  label = 'Placa (Identificador 1)',
  placeholder = 'Escanea o escribe la placa y presiona Enter',
  disabled,
  autoFocus = true,
  id,
  'data-testid': testId = 'scanner-input',
}: ScannerInputProps) {
  const autoId = useId()
  const inputId = id ?? autoId

  const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'Enter' && !disabled) {
      event.preventDefault()
      const placa = value.trim()
      if (placa.length > 0) onSubmit(placa)
    }
  }

  const handleFocus = (event: FocusEvent<HTMLInputElement>) => {
    event.target.select()
  }

  return (
    <div>
      <label htmlFor={inputId} className="mb-1.5 block text-sm font-medium text-slate-700">
        {label}
      </label>
      <input
        id={inputId}
        data-testid={testId}
        type="text"
        value={value}
        onChange={(event) => onChange(event.target.value)}
        onKeyDown={handleKeyDown}
        onFocus={handleFocus}
        disabled={disabled}
        autoFocus={autoFocus}
        autoComplete="off"
        spellCheck={false}
        placeholder={placeholder}
        className={cn(
          'w-full rounded-md border bg-surface px-3 py-2.5 font-mono text-sm text-slate-900 shadow-none outline-none transition-colors',
          'placeholder:font-sans placeholder:text-slate-400 focus:ring-2',
          bordePorEstado(status),
          anilloPorEstado(status),
        )}
      />
      <EstadoAlerta status={status} message={message} />
    </div>
  )
}
