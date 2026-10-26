<?php

namespace App\Enums;

/**
 * Causa de baja de un portátil (cuando `clasificacion == BAJA`).
 */
enum CausalBajaEnum: string
{
    case OBSOLETO = 'OBSOLETO';
    case DANO_TOTAL = 'DANO_TOTAL';

    public function label(): string
    {
        return match ($this) {
            self::OBSOLETO => 'Obsoleto',
            self::DANO_TOTAL => 'Daño total',
        };
    }
}
