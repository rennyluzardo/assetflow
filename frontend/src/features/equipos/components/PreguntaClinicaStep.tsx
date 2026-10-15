import type { PreguntaClinica } from '../cpuFlowRules'
import { cn } from '@/lib/cn'

interface PreguntaClinicaStepProps {
  pregunta: PreguntaClinica
  valor: boolean | undefined
  onResponder: (valor: boolean) => void
  deshabilitado?: boolean
}

/** Pregunta clínica Sí/No con botones grandes (ergonomía de taller). */
export function PreguntaClinicaStep({
  pregunta,
  valor,
  onResponder,
  deshabilitado,
}: PreguntaClinicaStepProps) {
  return (
    <section>
      <h3 className="text-sm font-medium text-slate-700">Evaluación clínica</h3>
      <p className="mt-1 text-base text-slate-900">{pregunta.texto}</p>
      <div className="mt-3 flex gap-3">
        <BotonRespuesta
          seleccionado={valor === true}
          etiqueta="Sí"
          onClick={() => onResponder(true)}
          deshabilitado={deshabilitado}
        />
        <BotonRespuesta
          seleccionado={valor === false}
          etiqueta="No"
          onClick={() => onResponder(false)}
          deshabilitado={deshabilitado}
        />
      </div>
    </section>
  )
}

function BotonRespuesta({
  seleccionado,
  etiqueta,
  onClick,
  deshabilitado,
}: {
  seleccionado: boolean
  etiqueta: string
  onClick: () => void
  deshabilitado?: boolean
}) {
  return (
    <button
      type="button"
      disabled={deshabilitado}
      onClick={onClick}
      className={cn(
        'min-w-24 rounded-md border px-5 py-3 text-sm font-semibold transition-colors',
        seleccionado
          ? 'border-slate-400 bg-slate-900 text-white'
          : 'border-line bg-surface text-slate-600 hover:border-slate-300 hover:bg-muted',
      )}
    >
      {etiqueta}
    </button>
  )
}
