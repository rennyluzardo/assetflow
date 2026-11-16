<?php

namespace App\Services\Traslados;

use App\Enums\TrasladoEstadoEnum;
use App\Http\Requests\Traslados\ListarTrasladosRequest;
use App\Imports\PlacasTrasladoImport;
use App\Models\InventarioGeneral;
use App\Models\Traslado;
use App\Models\TrasladoEquipo;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Máquina de 3 estados del módulo de traslados:
 *
 *   ABIERTO ──(agregar ≥1 equipo)──▶ bautizo de origen
 *   ABIERTO ──(despachar)──▶ EN_TRANSITO
 *   EN_TRANSITO ──(verificar 100% / recibir)──▶ RECIBIDO
 *   EN_TRANSITO ──(admin: revertir)──▶ ABIERTO
 *
 * Reglas de negocio:
 * - El código es autogenerado y consecutivo; el origen nace NULL y se "bautiza"
 *   con la ubicación actual del primer equipo escaneado.
 * - Todos los equipos de un lote deben provenir del mismo origen y ninguno
 *   puede estar ya en la ubicación de destino (validación "origen ≠ destino").
 * - La recepción exige el 100% de equipos verificados (k = N), la realiza un
 *   usuario distinto del creador y del despachador, y mueve todo el lote al
 *   destino sellando su `num_traslado`.
 */
final class TrasladoService
{
    /** Código base: los consecutivos históricos de Cubit arrancan en 328351. */
    private const CODIGO_INICIAL = 328350;

    public function registrar(array $datos, User $usuario): Traslado
    {
        return Traslado::create([
            'codigo' => $this->generarCodigo(),
            'zona_origen' => null,
            'ubicacion_origen' => null,
            'zona_destino' => $datos['zona_destino'],
            'ubicacion_destino' => $datos['ubicacion_destino'],
            'estado' => TrasladoEstadoEnum::ABIERTO,
            'observaciones' => $datos['observaciones'] ?? null,
            'creado_por_id' => $usuario->id,
        ]);
    }

    /**
     * @return array{data: list<Traslado>, meta: array<string, int|null>}
     */
    public function listar(ListarTrasladosRequest $request): array
    {
        $query = Traslado::query();

        if ($codigo = $request->validated('codigo')) {
            $query->where('codigo', 'LIKE', '%'.$codigo.'%');
        }

        if ($zonaOrigen = $request->validated('zona_origen')) {
            $query->where('zona_origen', 'LIKE', '%'.$zonaOrigen.'%');
        }

        if ($ubicacionOrigen = $request->validated('ubicacion_origen')) {
            $query->where('ubicacion_origen', 'LIKE', '%'.$ubicacionOrigen.'%');
        }

        if ($zonaDestino = $request->validated('zona_destino')) {
            $query->where('zona_destino', 'LIKE', '%'.$zonaDestino.'%');
        }

        if ($ubicacionDestino = $request->validated('ubicacion_destino')) {
            $query->where('ubicacion_destino', 'LIKE', '%'.$ubicacionDestino.'%');
        }

        if ($estado = $request->validated('estado')) {
            $query->where('estado', $estado);
        }

        $query->withCount([
            'equipos as equipos_total',
            'equipos as equipos_recibidos' => fn ($q) => $q->where('recibido', true),
        ]);

        $paginator = $query->orderByDesc('id')->paginate((int) $request->validated('per_page', 10));

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * Detalle completo de un traslado: cabecera con responsables y equipos
     * aplanados para el contrato de la API.
     *
     * @return array{traslado: Traslado, equipos: list<array<string, mixed>>}
     */
    public function detalle(Traslado $traslado): array
    {
        $equipos = $traslado->equipos()
            ->with('inventario', 'receptor')
            ->orderBy('id')
            ->get()
            ->map(fn (TrasladoEquipo $pivote): array => [
                'id' => $pivote->id,
                'placa_id' => $pivote->placa_id,
                'serial' => $pivote->inventario?->serial,
                'marca' => $pivote->inventario?->marca,
                'referencia' => $pivote->inventario?->referencia,
                'descripcion' => $pivote->inventario?->descripcion,
                'zona_origen' => $pivote->zona_origen_snapshot,
                'ubicacion_origen' => $pivote->ubicacion_origen_snapshot,
                'recibido' => $pivote->recibido,
                'recibido_at' => $pivote->recibido_at?->toIso8601String(),
                'recibido_por' => $pivote->receptor?->name,
            ])
            ->values()
            ->all();

        return [
            'traslado' => $traslado->load('creador', 'despachador', 'receptor'),
            'equipos' => $equipos,
        ];
    }

    /**
     * Consulta de un activo al escanear la placa en traslados: devuelve su
     * ubicación actual y, si ya viaja en otro traslado activo, ese traslado.
     *
     * @return array<string, mixed>
     */
    public function buscarEquipo(string $placaId): array
    {
        $inventario = InventarioGeneral::where('placa_id', $placaId)->firstOrFail();

        $activo = TrasladoEquipo::query()
            ->where('inventario_id', $inventario->id)
            ->whereHas('traslado', fn ($q) => $q->whereIn('estado', [
                TrasladoEstadoEnum::ABIERTO->value,
                TrasladoEstadoEnum::EN_TRANSITO->value,
            ]))
            ->with('traslado')
            ->latest('id')
            ->first();

        return [
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'marca' => $inventario->marca,
            'referencia' => $inventario->referencia,
            'descripcion' => $inventario->descripcion,
            'zona_actual' => $inventario->zona_actual,
            'ubicacion_actual' => $inventario->ubicacion_actual,
            'traslado_activo' => $activo
                ? [
                    'codigo' => $activo->traslado->codigo,
                    'estado' => $activo->traslado->estado->value,
                ]
                : null,
        ];
    }

    /**
     * Agrega un activo al lote ABIERTO. El primer equipo "bautiza" el origen;
     * los siguientes deben coincidir con ese origen. En todos los casos el
     * activo debe estar en una ubicación distinta a la del destino.
     */
    public function agregarEquipo(Traslado $traslado, string $placaId): TrasladoEquipo
    {
        $this->asegurarAbierto($traslado);

        $inventario = InventarioGeneral::where('placa_id', $placaId)->first();

        if (! $inventario) {
            throw ValidationException::withMessages([
                'placa_id' => "No existe un activo con la placa {$placaId}.",
            ]);
        }

        if ($this->tieneTrasladoActivo($inventario->id, $traslado->id)) {
            throw ValidationException::withMessages([
                'placa_id' => "El activo {$placaId} ya viaja en otro traslado activo.",
            ]);
        }

        if ($traslado->equipos()->where('inventario_id', $inventario->id)->exists()) {
            throw ValidationException::withMessages([
                'placa_id' => "El activo {$placaId} ya está en este traslado.",
            ]);
        }

        if ($inventario->ubicacion_actual === $traslado->ubicacion_destino) {
            throw ValidationException::withMessages([
                'placa_id' => "El equipo ya se encuentra en la ubicación de destino ({$traslado->ubicacion_destino}).",
            ]);
        }

        $zonaOrigen = $inventario->zona_actual;
        $ubicacionOrigen = $inventario->ubicacion_actual;

        if ($traslado->zona_origen === null) {
            $traslado->update([
                'zona_origen' => $zonaOrigen,
                'ubicacion_origen' => $ubicacionOrigen,
            ]);
        } elseif ($traslado->zona_origen !== $zonaOrigen || $traslado->ubicacion_origen !== $ubicacionOrigen) {
            throw ValidationException::withMessages([
                'placa_id' => "El activo {$placaId} proviene de {$zonaOrigen} / {$ubicacionOrigen}, distinto al origen del lote ({$traslado->zona_origen} / {$traslado->ubicacion_origen}).",
            ]);
        }

        return TrasladoEquipo::create([
            'traslado_id' => $traslado->id,
            'inventario_id' => $inventario->id,
            'placa_id' => $inventario->placa_id,
            'zona_origen_snapshot' => $zonaOrigen,
            'ubicacion_origen_snapshot' => $ubicacionOrigen,
        ]);
    }

    /**
     * Importa placas desde un CSV/XLSX dentro de un traslado ABIERTO.
     *
     * Soporta dos formatos:
     *  - El reporte real de Cubit (`repItemTrasladoElementos`), con cabecera
     *    "Identificador 1" detectada dinámicamente: cada fila se hace upsert en
     *    `inventario_general` (con la ubicación normalizada) y luego se asocia
     *    al lote. Los equipos nuevos quedan con `num_traslado = null` hasta que
     *    el traslado pase a RECIBIDO.
     *  - Una lista simple de una columna (solo placas), descartando cabeceras.
     *
     * @return array{agregados: int, errores: list<array{placa: string, error: string}>}
     */
    public function importarExcel(Traslado $traslado, UploadedFile $archivo, User $usuario): array
    {
        $this->asegurarAbierto($traslado);

        $hoja = $this->leerHoja($archivo);
        $indiceCabecera = $this->indiceCabeceraCubit($hoja);
        $placas = [];

        if ($indiceCabecera !== null) {
            foreach (array_slice($hoja, $indiceCabecera + 1) as $fila) {
                $placa = trim((string) ($fila[0] ?? ''));

                if ($placa === '') {
                    continue;
                }

                $this->upsertActivoCubit($fila, $usuario->id);
                $placas[] = $placa;
            }
        } else {
            foreach ($hoja as $fila) {
                $placa = trim((string) ($fila[0] ?? ''));

                if ($placa === '' || in_array(mb_strtolower($placa, 'UTF-8'), [
                    'placa', 'placa_id', 'placaid', 'identificador 1',
                ], true)) {
                    continue;
                }

                $placas[] = $placa;
            }
        }

        $agregados = 0;
        $errores = [];

        foreach (array_values(array_unique($placas)) as $placa) {
            try {
                $this->agregarEquipo($traslado, $placa);
                $agregados++;
            } catch (ValidationException $e) {
                $errores[] = [
                    'placa' => $placa,
                    'error' => implode(' ', $e->errors()['placa_id'] ?? [$e->getMessage()]),
                ];
            }
        }

        return [
            'agregados' => $agregados,
            'errores' => $errores,
        ];
    }

    /**
     * Quita un activo del lote ABIERTO. Si el lote queda vacío, el origen vuelve
     * a NULL para permitir re-bautizar con otro origen.
     */
    public function quitarEquipo(Traslado $traslado, int $equipoId): void
    {
        $this->asegurarAbierto($traslado);

        $pivote = $traslado->equipos()->findOrFail($equipoId);
        $pivote->delete();

        if ($traslado->equipos()->count() === 0) {
            $traslado->update([
                'zona_origen' => null,
                'ubicacion_origen' => null,
            ]);
        }
    }

    public function despachar(Traslado $traslado, User $usuario): Traslado
    {
        $this->asegurarAbierto($traslado);

        if ($traslado->equipos()->count() === 0) {
            throw ValidationException::withMessages([
                'traslado' => 'No se puede despachar un traslado sin equipos.',
            ]);
        }

        $traslado->update([
            'estado' => TrasladoEstadoEnum::EN_TRANSITO,
            'despachado_por_id' => $usuario->id,
            'despachado_at' => now(),
        ]);

        return $traslado->fresh();
    }

    /**
     * Marca como recibido un equipo concreto dentro de un traslado en tránsito
     * (idempotente: re-verificar no duplica el marcado).
     */
    public function verificarEquipo(Traslado $traslado, string $placaId, User $usuario): TrasladoEquipo
    {
        $this->asegurarEstado($traslado, TrasladoEstadoEnum::EN_TRANSITO);

        $pivote = $traslado->equipos()->where('placa_id', $placaId)->first();

        if (! $pivote) {
            throw ValidationException::withMessages([
                'placa_id' => "El activo {$placaId} no pertenece a este traslado.",
            ]);
        }

        if (! $pivote->recibido) {
            $pivote->update([
                'recibido' => true,
                'recibido_por_id' => $usuario->id,
                'recibido_at' => now(),
            ]);
        }

        return $pivote->fresh();
    }

    /**
     * Cierra el traslado exigiendo el 100% de equipos verificados (k = N).
     * Quien creó o despachó no puede recibirlo. Al recibir, el lote completo se
     * mueve a la ubicación de destino y su `num_traslado` queda sellado con el
     * código del traslado.
     */
    public function recibir(Traslado $traslado, User $usuario): Traslado
    {
        $this->asegurarEstado($traslado, TrasladoEstadoEnum::EN_TRANSITO);

        if ($usuario->id === $traslado->creado_por_id || $usuario->id === $traslado->despachado_por_id) {
            throw new AuthorizationException('Quien creó o despachó el traslado no puede recibirlo.');
        }

        $total = $traslado->equipos()->count();
        $recibidos = $traslado->equipos()->where('recibido', true)->count();

        if ($recibidos < $total) {
            throw ValidationException::withMessages([
                'traslado' => "No se puede confirmar la recepción: faltan ".($total - $recibidos)." de {$total} equipos por verificar.",
            ]);
        }

        DB::transaction(function () use ($traslado, $usuario): void {
            $traslado->update([
                'estado' => TrasladoEstadoEnum::RECIBIDO,
                'recibido_por_id' => $usuario->id,
                'recibido_at' => now(),
            ]);

            $inventarioIds = $traslado->equipos()->pluck('inventario_id');

            InventarioGeneral::whereIn('id', $inventarioIds)->update([
                'zona_actual' => $traslado->zona_destino,
                'ubicacion_actual' => $traslado->ubicacion_destino,
                'num_traslado' => $traslado->codigo,
            ]);
        });

        return $traslado->fresh();
    }

    /**
     * Reversión administrativa: devuelve un traslado EN_TRANSITO a ABIERTO,
     * limpiando el despacho y reseteando la verificación de todos sus equipos.
     * Exclusivo del rol `admin`.
     */
    public function revertirApertura(Traslado $traslado, User $usuario): Traslado
    {
        if (! $usuario->hasRole('admin')) {
            throw new AuthorizationException('Solo un administrador puede revertir el despacho.');
        }

        $this->asegurarEstado($traslado, TrasladoEstadoEnum::EN_TRANSITO);

        DB::transaction(function () use ($traslado): void {
            $traslado->update([
                'estado' => TrasladoEstadoEnum::ABIERTO,
                'despachado_por_id' => null,
                'despachado_at' => null,
            ]);

            $traslado->equipos()->update([
                'recibido' => false,
                'recibido_por_id' => null,
                'recibido_at' => null,
            ]);
        });

        return $traslado->fresh();
    }

    private function generarCodigo(): string
    {
        return (string) retry(3, function (): int {
            return DB::transaction(function (): int {
                $maximo = (int) (Traslado::withTrashed()
                    ->pluck('codigo')
                    ->max(fn (string $codigo): int => ctype_digit($codigo) ? (int) $codigo : 0) ?? 0);

                return max($maximo, self::CODIGO_INICIAL) + 1;
            });
        });
    }

    private function asegurarAbierto(Traslado $traslado): void
    {
        $this->asegurarEstado($traslado, TrasladoEstadoEnum::ABIERTO);
    }

    private function asegurarEstado(Traslado $traslado, TrasladoEstadoEnum $estado): void
    {
        if ($traslado->estado !== $estado) {
            throw ValidationException::withMessages([
                'traslado' => "Esta operación requiere un traslado en estado {$estado->label()}.",
            ]);
        }
    }

    private function tieneTrasladoActivo(int $inventarioId, int $trasladoId): bool
    {
        return TrasladoEquipo::query()
            ->where('inventario_id', $inventarioId)
            ->where('traslado_id', '!=', $trasladoId)
            ->whereHas('traslado', fn ($q) => $q->whereIn('estado', [
                TrasladoEstadoEnum::ABIERTO->value,
                TrasladoEstadoEnum::EN_TRANSITO->value,
            ]))
            ->exists();
    }

    /**
     * Lee la primera hoja del archivo como un arreglo crudo de filas, indicando
     * explícitamente el readerType de Maatwebsite/Excel según la extensión real
     * (evita `NoTypeDetectedException` en archivos sin tipo MIME inferible).
     *
     * @return list<list<string>>
     */
    private function leerHoja(UploadedFile $archivo): array
    {
        $hojas = Excel::toArray(
            new PlacasTrasladoImport,
            $archivo->getRealPath(),
            null,
            $this->readerType($archivo),
        );

        return $hojas[0] ?? [];
    }

    private function readerType(UploadedFile $archivo): string
    {
        return match (strtolower($archivo->getClientOriginalExtension())) {
            'xlsx' => \Maatwebsite\Excel\Excel::XLSX,
            'xls' => \Maatwebsite\Excel\Excel::XLS,
            'csv', 'txt' => \Maatwebsite\Excel\Excel::CSV,
            default => \Maatwebsite\Excel\Excel::XLSX,
        };
    }

    /**
     * Índice (base-0) de la fila de cabecera del reporte Cubit: aquella cuya
     * columna 0 es "Identificador 1". Devuelve null si el archivo no es Cubit
     * (se tratará como lista simple de placas).
     */
    private function indiceCabeceraCubit(array $hoja): ?int
    {
        foreach ($hoja as $indice => $fila) {
            if (mb_strtolower(trim((string) ($fila[0] ?? '')), 'UTF-8') === 'identificador 1') {
                return $indice;
            }
        }

        return null;
    }

    /**
     * Extrae el nombre limpio de una ubicación con prefijo de centro de costo
     * ("30052001965-SALA_DE_GARANTIA" -> "SALA_DE_GARANTIA").
     */
    private function normalizarUbicacion(string $ubicacion): string
    {
        return (string) preg_replace('/^\d+\s*-\s*/', '', trim($ubicacion));
    }

    /**
     * Upsert silencioso de una fila Cubit en `inventario_general`: crea el
     * activo (con `num_traslado = null` si es nuevo) o actualiza su identidad y
     * ubicación sin tocar su historial de traslados.
     *
     * @param  list<string>  $fila
     */
    private function upsertActivoCubit(array $fila, int $usuarioCargue): void
    {
        $placa = trim((string) ($fila[0] ?? ''));
        $serial = trim((string) ($fila[1] ?? ''));
        $referencia = trim((string) ($fila[2] ?? ''));
        $descripcion = trim((string) ($fila[3] ?? ''));
        $zona = trim((string) ($fila[4] ?? ''));
        $ubicacion = $this->normalizarUbicacion((string) ($fila[5] ?? ''));

        $existente = InventarioGeneral::where('placa_id', $placa)->first();

        if ($existente !== null) {
            $existente->update([
                'serial' => $serial !== '' ? $serial : $existente->serial,
                'referencia' => $referencia !== '' ? $referencia : $existente->referencia,
                'descripcion' => $descripcion !== '' ? $descripcion : $existente->descripcion,
                'zona_actual' => $zona !== '' ? $zona : $existente->zona_actual,
                'ubicacion_actual' => $ubicacion !== '' ? $ubicacion : $existente->ubicacion_actual,
            ]);

            return;
        }

        InventarioGeneral::create([
            'placa_id' => $placa,
            'serial' => $serial !== '' ? $serial : null,
            'referencia' => $referencia !== '' ? $referencia : null,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'zona_actual' => $zona !== '' ? $zona : null,
            'ubicacion_actual' => $ubicacion !== '' ? $ubicacion : null,
            'num_traslado' => null,
            'estado_actual' => null,
            'intervenido' => false,
            'fecha_cargue' => now(),
            'usuario_cargue' => $usuarioCargue,
        ]);
    }
}
