<?php

namespace App\Enums;

/**
 * Origen del daño cubierto por garantía (cuando `clasificacion == GARANTIA`).
 *
 * Fase 8: la garantía es exclusivamente de fábrica; el origen se fija
 * automáticamente a `FABRICA` y ya no es elegido por el analista.
 */
enum OrigenDanoGarantiaEnum: string
{
    case FABRICA = 'FABRICA';

    public function label(): string
    {
        return match ($this) {
            self::FABRICA => 'Fábrica',
        };
    }
}
