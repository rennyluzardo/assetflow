import type { EquipoRegistro, InventarioBusqueda } from '@/types/equipos'
import { ESTADO_LABELS } from '@/constants/equipos'
import { CATALOGO_PREGUNTAS } from './cpuFlowRules'
import { useEquipoForm } from './useEquipoForm'
import { TipoGestionStep } from './components/TipoGestionStep'
import { PreguntaClinicaStep } from './components/PreguntaClinicaStep'
import { DestinoStep } from './components/DestinoStep'
import { EstadoPreview } from './components/EstadoPreview'

interface EquipoFormProps {
  equipo: InventarioBusqueda
  onGuardado?: (registro: EquipoRegistro) => void
}

/** Formulario clínico reactivo paso a paso (máquina de estados). */
export function EquipoForm({ equipo, onGuardado }: EquipoFormProps) {
  const form = useEquipoForm({ equipo, onGuardado })
  const pregunta = form.preguntaActual ? CATALOGO_PREGUNTAS[form.preguntaActual] : null

  return (
    <div className="max-w-2xl rounded-lg border border-line bg-surface p-5">
      {form.reanudable && (
        <div className="mb-5 rounded-md border border-estado-ambar-line bg-estado-ambar px-4 py-3">
          <p className="text-sm text-estado-ambar-ink">
            Flujo abierto
            {form.estadoReanudacion ? ` (${ESTADO_LABELS[form.estadoReanudacion]})` : ''} — completar
            cierre.
          </p>
        </div>
      )}

      <div className="space-y-6">
        {!form.reanudable && (
          <TipoGestionStep
            seleccion={form.tipoGestion}
            onSeleccionar={form.setTipoGestion}
            deshabilitado={form.guardando}
          />
        )}

        {pregunta && (
          <PreguntaClinicaStep
            pregunta={pregunta}
            valor={form.respuestas[pregunta.id]}
            onResponder={(valor) => form.responder(pregunta.id, valor)}
            deshabilitado={form.guardando}
          />
        )}

        {form.completo && (
          <>
            <DestinoStep
              opciones={form.destinos}
              seleccion={form.destino}
              onSeleccionar={form.setDestino}
              deshabilitado={form.guardando}
            />
            <EstadoPreview
              estado={form.estadoPreview!}
              destino={form.destino}
              observaciones={form.observaciones}
              onObservacionesChange={form.setObservaciones}
              guardando={form.guardando}
              error={form.errorGuardar}
              onGuardar={form.guardar}
            />
          </>
        )}
      </div>
    </div>
  )
}
