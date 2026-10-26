import { cn } from '@/lib/cn'

interface Opcion<T extends string> {
  value: T
  label: string
}

/** Grupo de selección única con botones grandes (ergonomía de taller). */
export function OpcionGroup<T extends string>({
  titulo,
  opciones,
  seleccion,
  onSeleccionar,
  deshabilitado,
}: {
  titulo: string
  opciones: ReadonlyArray<Opcion<T>>
  seleccion: T | null
  onSeleccionar: (value: T) => void
  deshabilitado?: boolean
}) {
  return (
    <section>
      <h3 className="text-sm font-medium text-slate-700">{titulo}</h3>
      <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
        {opciones.map((opcion) => (
          <button
            key={opcion.value}
            type="button"
            disabled={deshabilitado}
            onClick={() => onSeleccionar(opcion.value)}
            className={cn(
              'rounded-md border px-4 py-3 text-left text-sm font-medium transition-colors',
              seleccion === opcion.value
                ? 'border-slate-400 bg-muted text-slate-900'
                : 'border-line bg-surface text-slate-600 hover:border-slate-300 hover:bg-muted',
            )}
          >
            {opcion.label}
          </button>
        ))}
      </div>
    </section>
  )
}

/** Campo de texto etiquetado, con variante mono para identificadores técnicos. */
export function CampoTexto({
  label,
  value,
  onChange,
  mono,
  placeholder,
  required,
  disabled,
}: {
  label: string
  value: string
  onChange: (value: string) => void
  mono?: boolean
  placeholder?: string
  required?: boolean
  disabled?: boolean
}) {
  return (
    <div>
      <label className="mb-1.5 block text-sm font-medium text-slate-700">
        {label}
        {required && <span className="text-estado-terracota-ink"> *</span>}
      </label>
      <input
        type="text"
        value={value}
        disabled={disabled}
        onChange={(event) => onChange(event.target.value)}
        placeholder={placeholder}
        className={cn(
          'w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-slate-400',
          mono && 'font-mono',
        )}
      />
    </div>
  )
}

/** Campo numérico para `cantidad_danos`, con hint de validación de la rama. */
export function CampoNumero({
  label,
  value,
  onChange,
  hint,
  disabled,
}: {
  label: string
  value: string
  onChange: (value: string) => void
  hint?: string
  disabled?: boolean
}) {
  return (
    <div>
      <label className="mb-1.5 block text-sm font-medium text-slate-700">
        {label}
        <span className="text-estado-terracota-ink"> *</span>
      </label>
      <input
        type="number"
        min={1}
        value={value}
        disabled={disabled}
        onChange={(event) => onChange(event.target.value)}
        className="w-full rounded-md border border-line bg-surface px-3 py-2 font-mono text-sm text-slate-900 outline-none transition-colors focus:border-slate-400"
      />
      {hint && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
    </div>
  )
}

/** Interruptor Sí/No (boolean) estilo taller. */
export function ToggleCampo({
  label,
  checked,
  onChange,
  disabled,
}: {
  label: string
  checked: boolean
  onChange: (value: boolean) => void
  disabled?: boolean
}) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      disabled={disabled}
      onClick={() => onChange(!checked)}
      className={cn(
        'flex items-center justify-between rounded-md border px-4 py-3 text-left text-sm font-medium transition-colors',
        checked
          ? 'border-slate-400 bg-muted text-slate-900'
          : 'border-line bg-surface text-slate-600 hover:border-slate-300 hover:bg-muted',
      )}
    >
      <span>{label}</span>
      <span className={cn('text-xs', checked ? 'text-estado-verde-ink' : 'text-slate-400')}>
        {checked ? 'Sí' : 'No'}
      </span>
    </button>
  )
}
