import type { PortatilBusqueda, PortatilRegistro } from './types'
import {
  CAUSALES,
  CAUSAL_LABELS,
  CLASIFICACIONES,
  CLASIFICACION_LABELS,
  GRAVEDADES,
  GRAVEDAD_LABELS,
  ORIGENES,
  ORIGEN_LABELS,
} from './types'
import { StatusBadge } from '@/components/StatusBadge'
import { UBICACION_LABELS } from '@/constants/equipos'
import { cn } from '@/lib/cn'
import { ApiError } from '@/lib/api'
import { DestinoStep } from '@/features/equipos/components/DestinoStep'
import { usePortatilForm } from './usePortatilForm'
import { CampoNumero, CampoTexto, OpcionGroup, ToggleCampo } from './components/campos'

interface PortatilFormProps {
  portatil: PortatilBusqueda
  onGuardado?: (registro: PortatilRegistro) => void
}

/** Formulario paso a paso de la Matriz de Clasificación de Laptops. */
export function PortatilForm({ portatil, onGuardado }: PortatilFormProps) {
  const form = usePortatilForm({ portatil, onGuardado })

  return (
    <div className="mx-auto w-full max-w-2xl space-y-6 rounded-lg border border-line bg-surface p-5">
      <section>
        <h3 className="text-sm font-medium text-slate-700">1. Clasificación</h3>
        <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
          {CLASIFICACIONES.map((c) => (
            <button
              key={c}
              type="button"
              disabled={form.guardando}
              onClick={() => form.setClasificacion(c)}
              className={cn(
                'rounded-md border px-4 py-3 text-left text-sm font-medium transition-colors',
                form.clasificacion === c
                  ? 'border-slate-400 bg-muted text-slate-900'
                  : 'border-line bg-surface text-slate-600 hover:border-slate-300 hover:bg-muted',
              )}
            >
              {CLASIFICACION_LABELS[c]}
            </button>
          ))}
        </div>
      </section>

      <section>
        <h3 className="text-sm font-medium text-slate-700">2. Campos técnicos de laptop</h3>
        <div className="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
          <ToggleCampo
            label="Test de Lenovo"
            checked={form.testLenovo}
            onChange={form.setTestLenovo}
            disabled={form.guardando}
          />
          <CampoTexto
            label="Serial del disco"
            mono
            required
            value={form.serialDisco}
            onChange={form.setSerialDisco}
            placeholder="Serial del disco SSD/HDD"
            disabled={form.guardando}
          />
        </div>
      </section>

      {form.clasificacion === 'NOVEDAD' && (
        <>
          <OpcionGroup
            titulo="Gravedad de la novedad"
            opciones={GRAVEDADES.map((v) => ({ value: v, label: GRAVEDAD_LABELS[v] }))}
            seleccion={form.gravedadNovedad}
            onSeleccionar={form.setGravedadNovedad}
            deshabilitado={form.guardando}
          />
          {form.gravedadNovedad === 'PRONUNCIADA' && (
            <>
              <CampoNumero
                label="Cantidad de daños"
                value={form.cantidadDanos}
                onChange={form.setCantidadDanos}
                hint="Una novedad pronunciada exige más de 1 daño."
                disabled={form.guardando}
              />
              <CampoTexto
                label="Piezas a solicitar"
                required
                value={form.piezasSolicitar}
                onChange={form.setPiezasSolicitar}
                placeholder="Piezas o componentes a solicitar"
                disabled={form.guardando}
              />
            </>
          )}
        </>
      )}

      {form.clasificacion === 'GARANTIA' && (
        <>
          <OpcionGroup
            titulo="Origen del daño en garantía"
            opciones={ORIGENES.map((v) => ({ value: v, label: ORIGEN_LABELS[v] }))}
            seleccion={form.origenDanoGarantia}
            onSeleccionar={form.setOrigenDanoGarantia}
            deshabilitado={form.guardando}
          />
          {form.origenDanoGarantia === 'FABRICA' && (
            <CampoTexto
              label="Ticket del proveedor"
              mono
              required
              value={form.ticketProveedor}
              onChange={form.setTicketProveedor}
              placeholder="Número de ticket del proveedor"
              disabled={form.guardando}
            />
          )}
          {form.origenDanoGarantia === 'INDUCIDO' && (
            <CampoTexto
              label="Piezas a solicitar"
              required
              value={form.piezasSolicitar}
              onChange={form.setPiezasSolicitar}
              placeholder="Piezas o componentes a comprar"
              disabled={form.guardando}
            />
          )}
        </>
      )}

      {form.clasificacion === 'DANO_REPARABLE' && (
        <>
          <CampoNumero
            label="Cantidad de daños"
            value={form.cantidadDanos}
            onChange={form.setCantidadDanos}
            hint="Máximo 3 daños reparables."
            disabled={form.guardando}
          />
          <CampoTexto
            label="Partes FRU"
            mono
            value={form.partesFru}
            onChange={form.setPartesFru}
            placeholder="Códigos de partes FRU (opcional)"
            disabled={form.guardando}
          />
          <CampoTexto
            label="Piezas a solicitar"
            required
            value={form.piezasSolicitar}
            onChange={form.setPiezasSolicitar}
            placeholder="Piezas o componentes a solicitar"
            disabled={form.guardando}
          />
        </>
      )}

      {form.clasificacion === 'BAJA' && (
        <OpcionGroup
          titulo="Causal de baja"
          opciones={CAUSALES.map((v) => ({ value: v, label: CAUSAL_LABELS[v] }))}
          seleccion={form.causalBaja}
          onSeleccionar={form.setCausalBaja}
          deshabilitado={form.guardando}
        />
      )}

      {form.completa && (
        <>
          <DestinoStep
            opciones={form.destinos}
            seleccion={form.destino}
            onSeleccionar={form.setDestino}
            deshabilitado={form.guardando}
          />

          <section>
            <h3 className="text-sm font-medium text-slate-700">3. Confirmar diagnóstico</h3>

            <div className="mt-3 rounded-md border border-line bg-muted p-4">
              <p className="flex items-center gap-2 text-sm text-slate-600">
                Al guardar pasará a estado:
                {form.estadoPreview && <StatusBadge estado={form.estadoPreview} />}
              </p>
              {form.destino && (
                <p className="mt-1.5 font-mono text-xs text-slate-500">
                  destino: {form.destino} · {UBICACION_LABELS[form.destino]}
                </p>
              )}
            </div>

            <label htmlFor="portatil-observaciones" className="mt-4 block text-sm font-medium text-slate-700">
              Observaciones técnicas <span className="font-normal text-slate-400">(opcional)</span>
            </label>
            <textarea
              id="portatil-observaciones"
              value={form.observaciones}
              onChange={(event) => form.setObservaciones(event.target.value)}
              disabled={form.guardando}
              rows={3}
              placeholder="Notas del daño, pruebas realizadas o pieza intervenida…"
              className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-slate-400"
            />

            {form.errorGuardar && (
              <p className="mt-2 text-sm text-estado-terracota-ink">
                {form.errorGuardar instanceof ApiError
                  ? form.errorGuardar.message
                  : 'No se pudo guardar el diagnóstico.'}
              </p>
            )}

            <button
              type="button"
              disabled={!form.puedeGuardar}
              onClick={form.guardar}
              className="mt-4 w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
            >
              {form.guardando ? 'Guardando…' : 'Guardar diagnóstico'}
            </button>
          </section>
        </>
      )}
    </div>
  )
}
