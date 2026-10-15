<?php

namespace App\Enums;

enum TipoGestionEnum: string
{
    case NOVEDAD = 'NOVEDAD';
    case DIAGNOSTICO = 'DIAGNOSTICO';
    case INTERVENCION = 'INTERVENCION';

    public function label(): string
    {
        return match ($this) {
            self::NOVEDAD => 'Novedad',
            self::DIAGNOSTICO => 'Diagnóstico',
            self::INTERVENCION => 'Intervención',
        };
    }
}
