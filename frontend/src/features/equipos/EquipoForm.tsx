import type { EquipoRegistro, InventarioBusqueda } from '@/types/equipos'
import { ESTADO_LABELS } from '@/constants/equipos'
import { cn } from '@/lib/cn'
import { CATALOGO_PREGUNTAS } from './cpuFlowRules'
import type { CampoTextoKey } from './cpuFlowRules'
import { useEquipoForm } from './useEquipoForm'
import { TipoGestionStep } from './components/TipoGestionStep'
import { PreguntaClinicaStep } from './components/PreguntaClinicaStep'
import { DestinoStep } from './components/DestinoStep'
import { EstadoPreview } from './components/EstadoPreview'

const CAMPOS_TEXTO: Record<CampoTextoKey, { label: string; mono?: boolean }> = {
  motivo_baja: { label: 'Motivo de la baja' },
  serial_disco: { label: 'Serial del disco' },
  ticket_garantia: { label: 'N° DE TICKET', mono: true },
  pieza_garantia: { label: 'NOMBRE DE LA PIEZA' },
}

interface EquipoFormProps {
  equipo: InventarioBusqueda
  onGuardado?: (registro: EquipoRegistro) => void
}

/** Formulario clínico reactivo paso a paso (máquina de estados). */
export function EquipoForm({ equipo, onGuardado }: EquipoFormProps) {
  const form = useEquipoForm({ equipo, onGuardado })
  const pregunta = form.preguntaActual ? CATALOGO_PREGUNTAS[form.preguntaActual] : null
  const esGarantia = form.textosRequeridos.includes('ticket_garantia')

  return (
    <div className="mx-auto w-full max-w-2xl rounded-lg border border-line bg-surface p-5">
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

        {form.textosRequeridos.length > 0 && (
          <section className="space-y-4">
            <h3 className="text-sm font-medium text-slate-700">
              {esGarantia ? 'INFORMACIÓN DE LA GARANTÍA' : 'Datos de la baja'}
            </h3>
            {form.textosRequeridos.map((clave) => (
              <div key={clave}>
                <label
                  htmlFor={`campo-${clave}`}
                  className="mb-1.5 block text-sm font-medium text-slate-700"
                >
                  {CAMPOS_TEXTO[clave].label}
                  <span className="text-estado-terracota-ink"> *</span>
                </label>
                <input
                  id={`campo-${clave}`}
                  type="text"
                  value={form.textos[clave]}
                  onChange={(event) => form.setTexto(clave, event.target.value)}
                  disabled={form.guardando}
                  className={cn(
                    'w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-slate-400',
                    CAMPOS_TEXTO[clave].mono && 'font-mono',
                  )}
                />
              </div>
            ))}
          </section>
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
              observacionesRequeridas={form.observacionesRequeridas}
              foto={form.foto}
              onFotoChange={form.setFoto}
              fotoRequerida={form.fotoRequerida}
              mostrarEvidencia={!form.reanudable}
              puedeGuardar={form.puedeGuardar}
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
