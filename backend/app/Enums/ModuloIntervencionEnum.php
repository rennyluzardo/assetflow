<?php

namespace App\Enums;

/**
 * Estaciones del taller que dejan intervención en la tabla maestra
 * (`inventario_general.modulo_intervencion`). Los valores coinciden
 * exactamente con lo que escriben `EquipoService`, `SopladoService` y
 * `PortatilService`.
 */
enum ModuloIntervencionEnum: string
{
    case EQUIPOS = 'equipos';
    case SOPLADO = 'soplado';
    case PORTATILES = 'portatiles';

    public function label(): string
    {
        return match ($this) {
            self::EQUIPOS => 'Diagnóstico CPU',
            self::SOPLADO => 'Soplado',
            self::PORTATILES => 'Portátiles',
        };
    }
}
