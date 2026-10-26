<?php

namespace App\Services\Inventario;

use App\Enums\EstadoEquipoEnum;
use App\Enums\InventarioOrdenableEnum;
use App\Enums\UbicacionDestinoEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * Traduce los parámetros `sort`/`direction` a un ORDER BY seguro.
 *
 * El identificador de columna se resuelve SIEMPRE contra
 * {@see InventarioOrdenableEnum}, por lo que nunca llega input crudo al SQL.
 * Las columnas con flujo operativo (estado, ubicación) se ordenan por su
 * posición en el proceso y no por el valor crudo del enum.
 */
final class OrdenInventarioService
{
    public function aplicar(Builder $query, ?string $sort, ?string $direction): void
    {
        $columna = InventarioOrdenableEnum::tryFrom((string) $sort);

        if ($columna === null) {
            $query->orderByDesc('id');

            return;
        }

        $sentido = $direction === 'desc' ? 'desc' : 'asc';
        $flujo = $this->mapaFlujo($columna);

        if ($flujo === null) {
            $query->orderBy($columna->value, $sentido);
        } else {
            $query->orderByRaw($this->expresionCaso($columna->value, $flujo).' '.$sentido);
        }

        // Desempate estable: sin él, paginar una columna no única puede
        // duplicar u omitir filas entre páginas.
        $query->orderByDesc('id');
    }

    /**
     * @return array<string, int>|null
     */
    private function mapaFlujo(InventarioOrdenableEnum $columna): ?array
    {
        $casos = match ($columna) {
            InventarioOrdenableEnum::ESTADO_ACTUAL => EstadoEquipoEnum::cases(),
            InventarioOrdenableEnum::UBICACION_ACTUAL => UbicacionDestinoEnum::cases(),
            default => null,
        };

        if ($casos === null) {
            return null;
        }

        $mapa = [];

        foreach ($casos as $caso) {
            $mapa[$caso->value] = $caso->ordenFlujo();
        }

        return $mapa;
    }

    /**
     * Construye un CASE con las posiciones de flujo. Las ramas provienen de
     * enums del dominio, nunca de la request (sin superficie de inyección).
     *
     * @param  array<string, int>  $flujo
     */
    private function expresionCaso(string $columna, array $flujo): string
    {
        $ramas = '';

        foreach ($flujo as $valor => $posicion) {
            $ramas .= " WHEN '".$valor."' THEN ".(int) $posicion;
        }

        // ELSE 0 coloca los activos sin estado/ubicación al inicio en ASC.
        return 'CASE '.$columna.$ramas.' ELSE 0 END';
    }
}
