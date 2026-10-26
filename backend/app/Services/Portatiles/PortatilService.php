<?php

namespace App\Services\Portatiles;

use App\Enums\EstadoEquipoEnum;
use App\Enums\UbicacionDestinoEnum;
use App\Http\Requests\Portatiles\ListarPortatilesRequest;
use App\Models\GarantiaPortatil;
use App\Models\InventarioGeneral;
use Illuminate\Support\Facades\DB;

/**
 * Orquestación del registro único de portátiles: búsqueda, diagnóstico inicial,
 * cierre hacia Almacén General y adjunto de evidencia fotográfica.
 */
final class PortatilService
{
    public function __construct(private readonly PortatilStateMachineService $stateMachine) {}

    /**
     * @return array{data: list<GarantiaPortatil>, meta: array<string, int|null>}
     */
    public function listar(ListarPortatilesRequest $request): array
    {
        $query = GarantiaPortatil::query();

        if ($placa = $request->validated('placa_id')) {
            $termino = mb_strtolower(trim($placa), 'UTF-8');
            $query->whereRaw('LOWER(placa_id) LIKE ?', ['%'.$termino.'%']);
        }

        if ($traslado = $request->validated('num_traslado')) {
            $query->where('num_traslado', $traslado);
        }

        if ($clasificacion = $request->validated('clasificacion')) {
            $query->where('clasificacion', $clasificacion);
        }

        if ($estado = $request->validated('estado')) {
            $query->where('estado', $estado);
        }

        $paginator = $query->orderByDesc('id')->paginate((int) $request->validated('per_page', 250));

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
     * Busca en inventario y verifica si hay un registro único abierto reanudable.
     *
     * @return array<string, mixed>
     */
    public function buscar(string $placaId): array
    {
        $inventario = InventarioGeneral::where('placa_id', $placaId)->firstOrFail();
        $registro = GarantiaPortatil::where('placa_id', $placaId)
            ->whereIn('estado', [
                EstadoEquipoEnum::PENDIENTE_GARANTIA->value,
                EstadoEquipoEnum::POR_REACONDICIONAR->value,
            ])
            ->latest('id')
            ->first();

        return [
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'referencia' => $inventario->referencia,
            'descripcion' => $inventario->descripcion,
            'num_traslado' => $inventario->num_traslado,
            'ubicacion_actual' => $inventario->ubicacion_actual?->value,
            'estado_actual' => $inventario->estado_actual?->value,
            'portatil_activo' => $registro
                ? [
                    'id' => $registro->id,
                    'estado' => $registro->estado->value,
                    'clasificacion' => $registro->clasificacion->value,
                ]
                : null,
        ];
    }

    /**
     * Crea el diagnóstico inicial dentro de una transacción.
     *
     * @param  array<string, mixed>  $datos
     */
    public function registrar(
        array $datos,
        EstadoEquipoEnum $estado,
        UbicacionDestinoEnum $destino,
        int $analistaId,
    ): GarantiaPortatil {
        $inventario = InventarioGeneral::where('placa_id', $datos['placa_id'])->firstOrFail();

        return DB::transaction(function () use ($inventario, $datos, $estado, $destino, $analistaId) {
            $registro = GarantiaPortatil::create([
                'inventario_general_id' => $inventario->id,
                'placa_id' => $inventario->placa_id,
                'serial' => $inventario->serial,
                'marca' => $inventario->marca,
                'referencia' => $inventario->referencia,
                'num_traslado' => $inventario->num_traslado,
                'clasificacion' => $datos['clasificacion'],
                'gravedad_novedad' => $datos['gravedad_novedad'] ?? null,
                'origen_dano_garantia' => $datos['origen_dano_garantia'] ?? null,
                'causal_baja' => $datos['causal_baja'] ?? null,
                'cantidad_danos' => $datos['cantidad_danos'] ?? null,
                'test_lenovo' => $datos['test_lenovo'],
                'serial_disco' => $datos['serial_disco'] ?? null,
                'ticket_proveedor' => $datos['ticket_proveedor'] ?? null,
                'partes_fru' => $datos['partes_fru'] ?? null,
                'piezas_solicitar' => $datos['piezas_solicitar'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'foto_evidencia' => $datos['foto_evidencia'] ?? null,
                'ubicacion_destino' => $destino,
                'estado' => $estado,
                'analista_id' => $analistaId,
            ]);

            $this->sincronizarInventario($registro, $analistaId);

            return $registro->fresh();
        });
    }

    /**
     * Ejecuta el Punto de Control de Cierre: muta el mismo registro único a
     * INTERVENIDO en Almacén General.
     */
    public function cerrar(
        GarantiaPortatil $registro,
        UbicacionDestinoEnum $destino,
        string $reparadoPor,
        int $analistaId,
        ?string $foto = null,
    ): GarantiaPortatil {
        $estado = $this->stateMachine->estadoCierre();

        return DB::transaction(function () use ($registro, $destino, $reparadoPor, $analistaId, $foto, $estado) {
            $registro->update([
                'ubicacion_destino' => $destino,
                'estado' => $estado,
                'reparado_por' => $reparadoPor,
                'foto_evidencia' => $foto ?? $registro->foto_evidencia,
            ]);

            $this->sincronizarInventario($registro, $analistaId);

            return $registro->fresh();
        });
    }

    public function adjuntarFoto(GarantiaPortatil $registro, string $ruta): GarantiaPortatil
    {
        $registro->update(['foto_evidencia' => $ruta]);

        return $registro->fresh();
    }

    private function sincronizarInventario(GarantiaPortatil $registro, int $analistaId): void
    {
        $registro->inventarioGeneral()->update([
            'estado_actual' => $registro->estado,
            'ubicacion_actual' => $registro->ubicacion_destino,
            'analista_intervencion' => $analistaId,
            'modulo_intervencion' => 'portatiles',
            'fecha_intervencion' => now(),
            'intervenido' => $registro->estado->esTerminal(),
        ]);
    }
}
