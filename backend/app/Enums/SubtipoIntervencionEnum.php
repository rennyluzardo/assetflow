<?php

namespace App\Enums;

/**
 * Subtipo de intervención de un portátil en sitio (cuando
 * `clasificacion == INTERVENCION`). Determina si cierra en `INTERVENIDO`
 * (cambio de pieza) o en `REPOTENCIADO` (repotenciación de RAM/Disco).
 */
enum SubtipoIntervencionEnum: string
{
    case CAMBIO_PIEZA = 'CAMBIO_PIEZA';
    case REPOTENCIACION = 'REPOTENCIACION';

    public function label(): string
    {
        return match ($this) {
            self::CAMBIO_PIEZA => 'Cambio de pieza',
            self::REPOTENCIACION => 'Repotenciación',
        };
    }
}
