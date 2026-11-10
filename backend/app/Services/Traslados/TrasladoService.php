<?php

namespace App\Services\Traslados;

use App\Enums\SedeOperativaEnum;
use App\Http\Requests\Traslados\ListarTrasladosRequest;
use App\Models\InventarioGeneral;
use App\Models\Traslado;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Orquestación del módulo de Traslados: registro de un lote en tránsito,
 * listado paginado, listado de equipos asociados y recepción con validación
 * de sede física del operario (en origen y en destino).
 */
final class TrasladoService
{
    public const EN_TRANSITO = 'EN_TRANSITO';

    public const RECIBIDO = 'RECIBIDO';

    public function registrar(array $datos, User $usuario): Traslado
    {
        $this->validarSedeOrigen($datos, $usuario);

        return Traslado::create([
            'codigo' => $datos['codigo'],
            'zona_origen' => $datos['zona_origen'],
            'ubicacion_origen_exacta' => $datos['ubicacion_origen_exacta'] ?? null,
            'zona_destino' => $datos['zona_destino'],
            'ubicacion_destino_exacta' => $datos['ubicacion_destino_exacta'] ?? null,
            'estado' => self::EN_TRANSITO,
            'analista_id' => $usuario->id,
        ]);
    }

    /**
     * @return array{data: list<Traslado>, meta: array<string, int|null>}
     */
    public function listar(ListarTrasladosRequest $request): array
    {
        $query = Traslado::query();

        if ($estado = $request->validated('estado')) {
            $query->where('estado', $estado);
        }

        $paginator = $query
            ->withCount('inventario as equipos_count')
            ->orderByDesc('id')
            ->paginate((int) $request->validated('per_page', 10));

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
     * Listado de equipos asociados a un traslado (por `num_traslado`), sin
     * mutar el estado. Alimenta la vista unificada de "listado de equipos".
     * `traslado` puede ser null si aún no se ha registrado el lote (los equipos
     * provienen del cargue masivo vía `num_traslado`).
     *
     * @return array{traslado: Traslado|null, equipos: Collection<int, InventarioGeneral>}
     */
    public function equipos(string $codigo): array
    {
        return [
            'traslado' => Traslado::where('codigo', $codigo)->first(),
            'equipos' => $this->equiposDelTraslado($codigo),
        ];
    }

    /**
     * Consulta de un activo al escanear la placa en el módulo de traslados:
     * devuelve su `num_traslado` y origen físico para validar la pertenencia
     * al lote y la coherencia de sede.
     *
     * @return array<string, mixed>
     */
    public function buscarEquipo(string $placaId): array
    {
        $inventario = InventarioGeneral::where('placa_id', $placaId)->firstOrFail();

        return [
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'marca' => $inventario->marca,
            'referencia' => $inventario->referencia,
            'descripcion' => $inventario->descripcion,
            'num_traslado' => $inventario->num_traslado,
            'ubicacion_origen' => $inventario->ubicacion_origen,
        ];
    }

    /**
     * Recibe un traslado en la sede del operario y devuelve los equipos que lo
     * componen. Bloquea (422) si la zona de destino del traslado no coincide con
     * la sede física del operario autenticado.
     *
     * @return array{traslado: Traslado, equipos: Collection<int, InventarioGeneral>}
     */
    public function recibir(string $codigo, User $usuario): array
    {
        $traslado = Traslado::where('codigo', $codigo)->firstOrFail();

        $this->validarSedeDestino($traslado, $usuario);

        $traslado->update(['estado' => self::RECIBIDO]);

        return [
            'traslado' => $traslado->fresh(),
            'equipos' => $this->equiposDelTraslado($codigo),
        ];
    }

    /**
     * Reconciliación del listado de traslados: crea la cabecera en `traslados`
     * para cada `num_traslado` distinto de `inventario_general` que aún no la
     * tenga. Es idempotente y no destructiva (nunca toca cabeceras existentes).
     *
     * @return array{creados: list<string>, existentes: int}
     */
    public function sincronizar(): array
    {
        $numeros = InventarioGeneral::query()
            ->whereNotNull('num_traslado')
            ->where('num_traslado', '!=', '')
            ->distinct()
            ->pluck('num_traslado');

        $existentes = Traslado::query()
            ->whereIn('codigo', $numeros)
            ->pluck('codigo')
            ->all();

        $creados = [];

        foreach ($numeros as $num) {
            if (in_array($num, $existentes, true)) {
                continue;
            }

            $origen = InventarioGeneral::query()
                ->where('num_traslado', $num)
                ->whereNotNull('ubicacion_origen')
                ->value('ubicacion_origen');

            Traslado::create([
                'codigo' => $num,
                'zona_origen' => SedeOperativaEnum::desdeOrigen($origen),
                'ubicacion_origen_exacta' => $origen,
                'zona_destino' => SedeOperativaEnum::BOGOTA,
                'ubicacion_destino_exacta' => null,
                'estado' => self::EN_TRANSITO,
                'analista_id' => null,
            ]);

            $creados[] = $num;
        }

        return [
            'creados' => $creados,
            'existentes' => count($numeros) - count($creados),
        ];
    }

    /**
     * Seguridad en origen: un operario solo puede originar traslados desde su
     * propia sede física. Es la contraparte de `validarSedeDestino` y bloquea
     * el registro (422) si `zona_origen` no corresponde con `users.sede`.
     */
    private function validarSedeOrigen(array $datos, User $usuario): void
    {
        // Sin sede asignada (administración/supervisión): no hay restricción.
        if ($usuario->sede === null || $usuario->sede === '') {
            return;
        }

        $sede = SedeOperativaEnum::tryFrom($usuario->sede);
        $origen = SedeOperativaEnum::tryFrom($datos['zona_origen']);

        if ($sede === null || $sede !== $origen) {
            throw ValidationException::withMessages([
                'zona_origen' => "Solo puede originar traslados desde su sede física ({$this->labelSede($sede, $usuario)}).",
            ]);
        }
    }

    private function validarSedeDestino(Traslado $traslado, User $usuario): void
    {
        // Sin sede asignada (administración/supervisión): no hay restricción de sede.
        if ($usuario->sede === null || $usuario->sede === '') {
            return;
        }

        $sede = SedeOperativaEnum::tryFrom($usuario->sede);

        if ($sede === null || $sede !== $traslado->zona_destino) {
            $destino = $traslado->zona_destino instanceof SedeOperativaEnum
                ? $traslado->zona_destino->label()
                : $traslado->zona_destino->value;

            throw ValidationException::withMessages([
                'codigo' => "Este traslado tiene como destino {$destino}, pero su sede física es {$this->labelSede($sede, $usuario)}. No puede recibirlo.",
            ]);
        }
    }

    /**
     * @return Collection<int, InventarioGeneral>
     */
    private function equiposDelTraslado(string $codigo): Collection
    {
        return InventarioGeneral::where('num_traslado', $codigo)
            ->orderBy('id')
            ->get([
                'id',
                'placa_id',
                'serial',
                'marca',
                'referencia',
                'descripcion',
                'num_traslado',
                'ubicacion_actual',
                'estado_actual',
                'intervenido',
                'ubicacion_origen',
            ]);
    }

    private function labelSede(?SedeOperativaEnum $sede, User $usuario): string
    {
        return $sede?->label() ?? $usuario->sede ?? 'sin asignar';
    }
}
