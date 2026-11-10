import type { PortatilBusqueda, PortatilRegistro } from './types'
import {
  CAUSALES,
  CAUSAL_LABELS,
  CLASIFICACIONES,
  CLASIFICACION_LABELS,
  GRAVEDADES,
  GRAVEDAD_LABELS,
  SUBTIPOS_INTERVENCION,
  SUBTIPO_LABELS,
} from './types'
import { StatusBadge } from '@/components/StatusBadge'
import { UBICACION_LABELS } from '@/constants/equipos'
import { cn } from '@/lib/cn'
import { ApiError } from '@/lib/api'
import { DestinoStep } from '@/features/equipos/components/DestinoStep'
import { MAX_DANOS, usePortatilForm } from './usePortatilForm'
import { CampoTexto, OpcionGroup, ToggleCampo } from './components/campos'

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
          {form.clasificacion === 'BAJA' && (
            <CampoTexto
              label="Serial del disco"
              mono
              required
              value={form.serialDisco}
              onChange={form.setSerialDisco}
              placeholder="Serial del disco SSD/HDD"
              disabled={form.guardando}
            />
          )}
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

          <CampoTexto
            label="Observaciones de la novedad"
            required
            value={form.observaciones}
            onChange={form.setObservaciones}
            placeholder="Comentarios de la novedad detectada"
            disabled={form.guardando}
          />

          {form.gravedadNovedad === 'PRONUNCIADA' && (
            <section className="space-y-3">
              <h3 className="text-sm font-medium text-slate-700">Componentes dañados</h3>
              {form.danos.map((dano, index) => (
                <div
                  key={index}
                  className="grid grid-cols-1 items-end gap-2 sm:grid-cols-[1fr_1fr_auto]"
                >
                  <div>
                    <label className="mb-1.5 block text-sm font-medium text-slate-700">
                      Nombre de la pieza <span className="text-estado-terracota-ink"> *</span>
                    </label>
                    <input
                      type="text"
                      value={dano.pieza}
                      onChange={(event) => form.actualizarDano(index, 'pieza', event.target.value)}
                      placeholder="Nombre de la pieza"
                      disabled={form.guardando}
                      className="w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-slate-400"
                    />
                  </div>
                  <div>
                    <label className="mb-1.5 block text-sm font-medium text-slate-700">
                      Código FRU <span className="font-normal text-slate-400">(opcional)</span>
                    </label>
                    <input
                      type="text"
                      value={dano.fru}
                      onChange={(event) => form.actualizarDano(index, 'fru', event.target.value)}
                      placeholder="Código FRU"
                      disabled={form.guardando}
                      className="w-full rounded-md border border-line bg-surface px-3 py-2 font-mono text-sm text-slate-900 outline-none transition-colors focus:border-slate-400"
                    />
                  </div>
                  <button
                    type="button"
                    onClick={() => form.eliminarDano(index)}
                    disabled={form.danos.length <= 1 || form.guardando}
                    aria-label={`Eliminar pieza ${index + 1}`}
                    className="rounded-md border border-line px-3 py-2 text-sm text-slate-500 transition-colors hover:text-estado-terracota-ink disabled:cursor-not-allowed disabled:opacity-40"
                  >
                    Quitar
                  </button>
                </div>
              ))}
              <button
                type="button"
                onClick={form.agregarDano}
                disabled={form.danos.length >= MAX_DANOS || form.guardando}
                className="rounded-md border border-dashed border-line px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
              >
                {form.danos.length >= MAX_DANOS ? 'Límite de 4 daños alcanzado' : '+ Agregar pieza'}
              </button>
            </section>
          )}
        </>
      )}

      {form.clasificacion === 'GARANTIA' && (
        <section className="space-y-4">
          <h3 className="text-sm font-medium text-slate-700">INFORMACIÓN DE LA GARANTÍA</h3>
          <div className="rounded-md border border-estado-ambar-line bg-estado-ambar px-4 py-3 text-sm text-estado-ambar-ink">
            Garantía exclusivamente de fábrica.
          </div>
          <CampoTexto
            label="N° DE TICKET"
            mono
            required
            value={form.ticketProveedor}
            onChange={form.setTicketProveedor}
            placeholder="Número de ticket del proveedor"
            disabled={form.guardando}
          />
          <CampoTexto
            label="NOMBRE DE LA PIEZA"
            required
            value={form.piezasSolicitar}
            onChange={form.setPiezasSolicitar}
            placeholder="Pieza sobre la que aplica la garantía"
            disabled={form.guardando}
          />
        </section>
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

      {form.clasificacion === 'INTERVENCION' && (
        <>
          <ToggleCampo
            label="¿La pieza es nueva?"
            checked={form.piezaEsNueva}
            onChange={form.setPiezaEsNueva}
            disabled={form.guardando}
          />
          <OpcionGroup
            titulo="Subtipo de intervención"
            opciones={SUBTIPOS_INTERVENCION.map((v) => ({ value: v, label: SUBTIPO_LABELS[v] }))}
            seleccion={form.subtipoIntervencion}
            onSeleccionar={form.setSubtipoIntervencion}
            deshabilitado={form.guardando}
          />
          <CampoTexto
            label="Piezas a solicitar"
            required
            value={form.piezasSolicitar}
            onChange={form.setPiezasSolicitar}
            placeholder="Piezas o componentes a instalar"
            disabled={form.guardando}
          />
          {form.subtipoIntervencion === 'REPOTENCIACION' && (
            <CampoTexto
              label="Especificación de la repotenciación"
              required
              value={form.especificacionRepotenciacion}
              onChange={form.setEspecificacionRepotenciacion}
              placeholder="Ej. capacidad final de RAM/Disco"
              disabled={form.guardando}
            />
          )}
        </>
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

            {form.clasificacion !== 'NOVEDAD' && (
              <label
                htmlFor="portatil-observaciones"
                className="mt-4 block text-sm font-medium text-slate-700"
              >
                Observaciones técnicas{' '}
                <span className="font-normal text-slate-400">(opcional)</span>
              </label>
            )}
            {form.clasificacion !== 'NOVEDAD' && (
              <textarea
                id="portatil-observaciones"
                value={form.observaciones}
                onChange={(event) => form.setObservaciones(event.target.value)}
                disabled={form.guardando}
                rows={3}
                placeholder="Notas del daño, pruebas realizadas o pieza intervenida…"
                className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-slate-900 outline-none transition-colors focus:border-slate-400"
              />
            )}

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
