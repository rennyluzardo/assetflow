<?php

namespace App\Enums;

/**
 * Columnas ordenables de la tabla maestra de inventario.
 *
 * Los valores coinciden exactamente con los ids de columna del frontend
 * (`features/inventario/columnas.tsx`), de modo que el parámetro `sort` viaja
 * sin traducción entre las dos capas.
 */
enum InventarioOrdenableEnum: string
{
    case PLACA_ID = 'placa_id';
    case SERIAL = 'serial';
    case DESCRIPCION = 'descripcion';
    case MARCA = 'marca';
    case NUM_TRASLADO = 'num_traslado';
    case UBICACION_ACTUAL = 'ubicacion_actual';
    case ESTADO_ACTUAL = 'estado_actual';
    case INTERVENIDO = 'intervenido';

    /**
     * Valores admitidos por la regla `Rule::in` del FormRequest.
     *
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $columna): string => $columna->value, self::cases());
    }
}
