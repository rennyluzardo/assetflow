import { useRef, useState } from 'react'
import type { ChangeEvent, DragEvent, FormEvent } from 'react'
import { ApiError } from '@/lib/api'
import { cn } from '@/lib/cn'
import { useProcesarCargueMasivo } from './api'
import type { CargueResultado } from './types'

const CABECERAS_PLANTILLA = [
  'Identificador 1',
  'Identificador 2',
  'Marca',
  'Ref. Principal',
  'Descripcion',
  'Zona actual',
] as const

/** Descarga un CSV de plantilla con las cabeceras oficiales del formato Cubit. */
function descargarPlantilla(): void {
  const filaEjemplo = ['AF-100001', 'SN-100001', 'Dell', 'OptiPlex 7050', 'CPU', 'Almacén']
  const csv = [CABECERAS_PLANTILLA.join(','), filaEjemplo.join(',')].join('\n')

  // BOM UTF-8 para que Excel abra tildes correctamente.
  const blob = new Blob([`﻿${csv}`], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const enlace = document.createElement('a')
  enlace.href = url
  enlace.download = 'plantilla_cargue_assetflow.csv'
  enlace.click()
  URL.revokeObjectURL(url)
}

/** Pantalla de ingesta masiva: archivo Cubit (CSV/XLSX) + número de traslado. */
export function CargueMasivoScreen() {
  const [numTraslado, setNumTraslado] = useState('')
  const [archivo, setArchivo] = useState<File | null>(null)
  const [errorArchivo, setErrorArchivo] = useState<string | null>(null)
  const [arrastrando, setArrastrando] = useState(false)
  const [resultado, setResultado] = useState<CargueResultado | null>(null)
  const inputArchivo = useRef<HTMLInputElement>(null)

  const procesar = useProcesarCargueMasivo()

  const aceptarArchivo = (candidato: File | null | undefined) => {
    setErrorArchivo(null)
    setResultado(null)

    if (!candidato) return

    const extension = candidato.name.split('.').pop()?.toLowerCase() ?? ''
    if (!['csv', 'xlsx'].includes(extension)) {
      setErrorArchivo('Solo se admiten archivos CSV o XLSX.')
      setArchivo(null)
      return
    }

    setArchivo(candidato)
  }

  const handleSeleccion = (event: ChangeEvent<HTMLInputElement>) => {
    aceptarArchivo(event.target.files?.[0])
    if (inputArchivo.current) inputArchivo.current.value = ''
  }

  const handleDrop = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault()
    setArrastrando(false)
    aceptarArchivo(event.dataTransfer.files?.[0])
  }

  const puedeEnviar = archivo !== null && numTraslado.trim() !== '' && !procesar.isPending

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (archivo === null || numTraslado.trim() === '') return

    procesar.mutate(
      { num_traslado: numTraslado.trim(), archivo },
      { onSuccess: (res) => setResultado(res) },
    )
  }

  return (
    <div className="flex flex-col gap-6 p-6">
      <header>
        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Cargue Masivo</h1>
        <p className="mt-1 text-sm text-slate-500">
          Ingesta de lotes desde el formato Cubit de Teleperformance.
        </p>
      </header>

      <form
        onSubmit={handleSubmit}
        className="max-w-2xl space-y-6 rounded-lg border border-line bg-surface p-5"
      >
        <div>
          <label htmlFor="num_traslado" className="mb-1.5 block text-sm font-medium text-slate-700">
            Número de Traslado
          </label>
          <input
            id="num_traslado"
            type="text"
            value={numTraslado}
            onChange={(event) => setNumTraslado(event.target.value)}
            disabled={procesar.isPending}
            placeholder="Ej. TR-2026-0001"
            autoComplete="off"
            spellCheck={false}
            className="w-full rounded-md border border-line bg-surface px-3 py-2.5 font-mono text-sm text-slate-900 outline-none transition-colors placeholder:font-sans placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-300"
          />
        </div>

        <div>
          <p className="mb-1.5 text-sm font-medium text-slate-700">Archivo del lote</p>
          <div
            role="button"
            tabIndex={0}
            onClick={() => inputArchivo.current?.click()}
            onKeyDown={(event) => {
              if (event.key === 'Enter' || event.key === ' ') inputArchivo.current?.click()
            }}
            onDragOver={(event) => {
              event.preventDefault()
              setArrastrando(true)
            }}
            onDragLeave={() => setArrastrando(false)}
            onDrop={handleDrop}
            className={cn(
              'flex cursor-pointer flex-col items-center justify-center rounded-md border border-dashed px-4 py-8 text-center transition-colors',
              arrastrando
                ? 'border-slate-400 bg-muted'
                : 'border-line bg-canvas hover:border-slate-300 hover:bg-muted',
            )}
          >
            <input
              ref={inputArchivo}
              type="file"
              accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
              onChange={handleSeleccion}
              disabled={procesar.isPending}
              className="hidden"
            />
            {archivo ? (
              <p className="font-mono text-sm text-slate-900">{archivo.name}</p>
            ) : (
              <>
                <p className="text-sm font-medium text-slate-700">
                  Arrastra el archivo aquí o haz clic para seleccionarlo
                </p>
                <p className="mt-1 text-xs text-slate-400">Formatos admitidos: .csv y .xlsx</p>
              </>
            )}
          </div>
          {errorArchivo && <p className="mt-1.5 text-sm text-estado-terracota-ink">{errorArchivo}</p>}
        </div>

        <div className="flex flex-wrap items-center gap-3">
          <button
            type="button"
            onClick={descargarPlantilla}
            disabled={procesar.isPending}
            className="rounded-md border border-line bg-canvas px-3 py-1.5 text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
          >
            Descargar Plantilla Oficial
          </button>
        </div>

        {procesar.isError && (
          <p className="text-sm text-estado-terracota-ink">
            {procesar.error instanceof ApiError
              ? procesar.error.message
              : 'No se pudo procesar el archivo.'}
          </p>
        )}

        <button
          type="submit"
          disabled={!puedeEnviar}
          className="w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
        >
          {procesar.isPending ? 'Procesando lote…' : 'Procesar cargue'}
        </button>
      </form>

      {resultado && <ResumenResultado resultado={resultado} />}
    </div>
  )
}

/** Resumen posterior a la ingesta: insertados, omitidos y detalle de errores. */
function ResumenResultado({ resultado }: { resultado: CargueResultado }) {
  return (
    <section className="max-w-2xl rounded-lg border border-estado-verde-line bg-estado-verde p-5">
      <h2 className="text-sm font-medium text-estado-verde-ink">Lote procesado — {resultado.num_traslado}</h2>
      <dl className="mt-3 grid grid-cols-2 gap-4">
        <div>
          <dt className="text-xs text-estado-verde-ink/70">Insertados</dt>
          <dd className="text-xl font-semibold text-estado-verde-ink">{resultado.insertados}</dd>
        </div>
        <div>
          <dt className="text-xs text-estado-verde-ink/70">Omitidos</dt>
          <dd className="text-xl font-semibold text-estado-verde-ink">{resultado.omitidos}</dd>
        </div>
      </dl>

      {resultado.errores.length > 0 && (
        <ul className="mt-4 space-y-1.5 border-t border-estado-verde-line pt-4">
          {resultado.errores.map((error) => (
            <li key={`${error.fila}-${error.error}`} className="text-xs text-estado-verde-ink">
              <span className="font-mono">Fila {error.fila}:</span> {error.error}
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
