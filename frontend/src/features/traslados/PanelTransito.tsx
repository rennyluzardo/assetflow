import { useState } from 'react'
import { DrawerFooter } from '@/components/Drawer'
import { ScannerInput } from '@/components/ScannerInput'
import type { ScannerStatus } from '@/components/ScannerInput'
import { useAuth } from '@/features/auth/AuthContext'
import { useRecibir, useRevertirApertura, useVerificarEquipo } from './api'
import { mensajeError } from './componentes'
import { ListaEquipos } from './ListaEquipos'
import type { EquipoTraslado } from './types'

interface PanelTransitoProps {
  trasladoId: number
  equipos: EquipoTraslado[]
}

/** Panel del traslado EN_TRANSITO: verificar el 100% de equipos, confirmar la recepción o revertir. */
export function PanelTransito({ trasladoId, equipos }: PanelTransitoProps) {
  const { session } = useAuth()
  const [placa, setPlaca] = useState('')
  const [status, setStatus] = useState<ScannerStatus>('idle')
  const [message, setMessage] = useState<string | null>(null)

  const verificar = useVerificarEquipo(trasladoId)
  const recibir = useRecibir(trasladoId)
  const revertir = useRevertirApertura(trasladoId)

  const esAdmin = session?.roles.includes('admin') ?? false
  const recibidos = equipos.filter((equipo) => equipo.recibido).length
  const faltan = equipos.length - recibidos
  const completo = faltan === 0

  const escanear = (valor: string) => {
    verificar.mutate(valor, {
      onSuccess: (pivote) => {
        setPlaca('')
        setStatus('success')
        setMessage(`Placa ${pivote.placa_id} verificada como recibida.`)
      },
      onError: (error) => {
        setStatus('error')
        setMessage(mensajeError(error))
      },
    })
  }

  const devolverAAbierto = () => {
    if (
      !window.confirm(
        '¿Devolver este traslado a Abierto? Se borrará el despacho y se reseteará la verificación de todos sus equipos.',
      )
    ) {
      return
    }

    revertir.mutate(undefined, {
      onSuccess: () => {
        setStatus('success')
        setMessage('Traslado devuelto a Abierto.')
      },
      onError: (error) => {
        setStatus('error')
        setMessage(mensajeError(error))
      },
    })
  }

  return (
    <div className="space-y-4">
      {/* Cinta sticky exclusiva del escáner: cubre de lado a lado el body (`px-5`). */}
      <div className="sticky top-0 z-20 -mx-5 bg-white px-5 pb-3 pt-2">
        <ScannerInput
          value={placa}
          onChange={setPlaca}
          onSubmit={escanear}
          status={status}
          message={message}
          label="Verificación de recepción"
          placeholder="Escanea la placa recibida y presiona Enter"
          disabled={verificar.isPending}
        />
      </div>

      <div>
        <h3 className="mb-2 text-sm font-semibold text-slate-700">
          Equipos del traslado ({recibidos}/{equipos.length} recibidos)
        </h3>
        <ListaEquipos equipos={equipos} />
      </div>

      {!completo && (
        <p className="text-sm text-slate-500">
          Escanea los {faltan} equipos faltantes para habilitar el cierre de recepción.
        </p>
      )}

      <DrawerFooter>
        <div className="space-y-3">
          <button
            type="button"
            onClick={() => recibir.mutate()}
            disabled={recibir.isPending || !completo}
            className="w-full rounded-md bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {recibir.isPending ? 'Confirmando…' : 'Confirmar recepción'}
          </button>

          {recibir.isError && (
            <p className="text-sm text-estado-terracota-ink">{mensajeError(recibir.error)}</p>
          )}

          {esAdmin && (
            <button
              type="button"
              onClick={devolverAAbierto}
              disabled={revertir.isPending}
              className="w-full rounded-md border border-line px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40"
            >
              {revertir.isPending ? 'Devolviendo…' : 'Devolver a Abierto'}
            </button>
          )}

          {revertir.isError && (
            <p className="text-sm text-estado-terracota-ink">{mensajeError(revertir.error)}</p>
          )}
        </div>
      </DrawerFooter>
    </div>
  )
}
