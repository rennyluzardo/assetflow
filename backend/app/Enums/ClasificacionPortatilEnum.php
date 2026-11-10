<?php

namespace App\Enums;

/**
 * Clasificación raíz de la Matriz de Clasificación de Laptops.
 *
 * Es el análogo de `TipoGestionEnum` en el flujo de CPUs: la primera decisión
 * del árbol que determina los campos requeridos, el destino y el estado.
 */
enum ClasificacionPortatilEnum: string
{
    case FUNCIONAL = 'FUNCIONAL';
    case NOVEDAD = 'NOVEDAD';
    case GARANTIA = 'GARANTIA';
    case BAJA = 'BAJA';
    case INTERVENCION = 'INTERVENCION';

    public function label(): string
    {
        return match ($this) {
            self::FUNCIONAL => 'Funcional',
            self::NOVEDAD => 'Novedad',
            self::GARANTIA => 'Garantía',
            self::BAJA => 'Baja',
            self::INTERVENCION => 'Intervención',
        };
    }
}
