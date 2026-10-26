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
    case DANO_REPARABLE = 'DANO_REPARABLE';
    case BAJA = 'BAJA';

    public function label(): string
    {
        return match ($this) {
            self::FUNCIONAL => 'Funcional',
            self::NOVEDAD => 'Novedad',
            self::GARANTIA => 'Garantía',
            self::DANO_REPARABLE => 'Daño reparable',
            self::BAJA => 'Baja',
        };
    }
}
