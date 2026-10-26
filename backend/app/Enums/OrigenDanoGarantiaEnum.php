<?php

namespace App\Enums;

/**
 * Origen del daño cubierto por garantía (cuando `clasificacion == GARANTIA`).
 */
enum OrigenDanoGarantiaEnum: string
{
    case FABRICA = 'FABRICA';
    case INDUCIDO = 'INDUCIDO';

    public function label(): string
    {
        return match ($this) {
            self::FABRICA => 'Fábrica',
            self::INDUCIDO => 'Inducido',
        };
    }
}
