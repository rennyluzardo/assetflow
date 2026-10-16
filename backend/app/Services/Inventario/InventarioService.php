<?php

namespace App\Services\Inventario;

use App\Http\Requests\Inventario\ListarInventarioRequest;
use App\Models\InventarioGeneral;

/**
 * Lectura de la tabla maestra de inventario con paginación optimizada,
 * búsqueda sobre identificadores y filtros operativos.
 */
final class InventarioService
{
    /**
     * Columnas proyectadas para la tabla maestra (sin cargar relaciones).
     *
     * @var list<string>
     */
    private const COLUMNAS = [
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
    ];

    /**
     * @return array{data: list<InventarioGeneral>, meta: array<string, int|null>}
     */
    public function listar(ListarInventarioRequest $request): array
    {
        $query = InventarioGeneral::query()->select(self::COLUMNAS);

        if ($busqueda = $request->validated('search')) {
            $termino = mb_strtolower(trim($busqueda), 'UTF-8');
            $patron = '%'.$termino.'%';

            $query->where(function ($subquery) use ($patron): void {
                $subquery->whereRaw('LOWER(placa_id) LIKE ?', [$patron])
                    ->orWhereRaw('LOWER(serial) LIKE ?', [$patron])
                    ->orWhereRaw('LOWER(descripcion) LIKE ?', [$patron]);
            });
        }

        if ($traslado = $request->validated('num_traslado')) {
            $query->where('num_traslado', $traslado);
        }

        if ($estado = $request->validated('estado_actual')) {
            if ($estado === 'SIN_ESTADO') {
                $query->whereNull('estado_actual');
            } else {
                $query->where('estado_actual', $estado);
            }
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
}
