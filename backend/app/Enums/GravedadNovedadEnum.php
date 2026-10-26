<?php

namespace App\Enums;

/**
 * Gravedad de una novedad detectada en un portátil (cuando
 * `clasificacion == NOVEDAD`). Determina si cierra en Almacén o abre flujo.
 */
enum GravedadNovedadEnum: string
{
    case MINIMA = 'MINIMA';
    case PRONUNCIADA = 'PRONUNCIADA';

    public function label(): string
    {
        return match ($this) {
            self::MINIMA => 'Mínima',
            self::PRONUNCIADA => 'Pronunciada',
        };
    }
}
