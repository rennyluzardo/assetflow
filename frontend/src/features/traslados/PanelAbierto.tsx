import { useRef, useState } from 'react'
import { ScannerInput } from '@/components/ScannerInput'
import type { ScannerStatus } from '@/components/ScannerInput'
import { useAgregarEquipo, useDespachar, useImportarEquipos, useQuitarEquipo } from './api'
import { mensajeError } from './componentes'
import { ListaEquipos } from './ListaEquipos'
import type { EquipoTraslado } from './types'

interface PanelAbiertoProps {
  trasladoId: number
  equipos: EquipoTraslado[]
}

/** Panel del traslado ABIERTO: escaneo/importe de equipos, quitar y despachar. */
export function PanelAbierto({ trasladoId, equipos }: PanelAbiertoProps) {
  const [placa, setPlaca] = useState('')
  const [status, setStatus] = useState<ScannerStatus>('idle')
  const [message, setMessage] = useState<string | null>(null)

  const agregar = useAgregarEquipo(trasladoId)
  const quitar = useQuitarEquipo(trasladoId)
  const importar = useImportarEquipos(trasladoId)
  const despachar = useDespachar(trasladoId)
  const inputArchivo = useRef<HTMLInputElement>(null)

  const escanear = (valor: string) => {
    agregar.mutate(valor, {
      onSuccess: (pivote) => {
        setPlaca('')
        setStatus('success')
        setMessage(`Placa ${pivote.placa_id} agregada al lote.`)
      },
      onError: (error) => {
        setStatus('error')
        setMessage(mensajeError(error))
      },
    })
  }

  const seleccionarArchivo = (archivo: File | null) => {
    if (!archivo) return
    importar.mutate(archivo, {
      onSuccess: (resultado) => {
        setStatus(resultado.errores.length === 0 ? 'success' : 'warning')
        setMessage(
          `Importadas ${resultado.agregados} placas${resultado.errores.length ? ` · ${resultado.errores.length} con error` : ''}.`,
        )
      },
      onError: (error) => {
        setStatus('error')
        setMessage(mensajeError(error))
      },
    })
    if (inputArchivo.current) inputArchivo.current.value = ''
  }

  return (
    <div className="space-y-4">
      <ScannerInput
        value={placa}
        onChange={setPlaca}
        onSubmit={escanear}
        status={status}
        message={message}
        label="Escaneo de equipos"
        placeholder="Escanea la placa y presiona Enter"
        disabled={agregar.isPending}
      />

      <div>
        <input
          ref={inputArchivo}
          type="file"
          accept=".csv,.xlsx"
          className="hidden"
          onChange={(event) => seleccionarArchivo(event.target.files?.[0] ?? null)}
        />
        <button
          type="button"
          onClick={() => inputArchivo.current?.click()}
          disabled={importar.isPending}
          className="rounded-md border border-line px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
        >
          {importar.isPending ? 'Importando…' : 'Importar desde Excel/CSV'}
        </button>
        <p className="mt-1 text-xs text-slate-400">
          Puente transitorio de carga desde reporte Cubit (.xlsx / .csv)
        </p>
      </div>

      <div>
        <h3 className="mb-2 text-sm font-semibold text-slate-700">Equipos del lote</h3>
        <ListaEquipos
          equipos={equipos}
          onQuitar={(equipo) => quitar.mutate(equipo.id)}
          quitarPendiente={quitar.isPending}
        />
      </div>

      <button
        type="button"
        onClick={() => despachar.mutate()}
        disabled={despachar.isPending || equipos.length === 0}
        className="w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
      >
        {despachar.isPending ? 'Despachando…' : 'Despachar traslado'}
      </button>

      {despachar.isError && (
        <p className="text-sm text-estado-terracota-ink">{mensajeError(despachar.error)}</p>
      )}
    </div>
  )
}
