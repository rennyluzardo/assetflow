interface ModulePlaceholderProps {
  title: string
  description?: string
}

/** Pantalla mínima para módulos aún no implementados en la fase actual. */
export function ModulePlaceholder({ title, description }: ModulePlaceholderProps) {
  return (
    <div className="flex h-full flex-col gap-4 p-6">
      <header>
        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">{title}</h1>
        {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
      </header>
      <div className="mx-auto flex w-full max-w-2xl flex-1 items-center justify-center rounded-lg border border-dashed border-line bg-surface p-10">
        <p className="text-sm text-slate-400">Módulo en construcción — fase posterior.</p>
      </div>
    </div>
  )
}
