<?php

namespace App\Enums;

enum EstadoEquipoEnum: string
{
    case POR_SOPLAR = 'POR_SOPLAR';
    case PENDIENTE_GARANTIA = 'PENDIENTE_GARANTIA';
    case POR_REACONDICIONAR = 'POR_REACONDICIONAR';
    case COMPLETADO = 'COMPLETADO';
    case INTERVENIDO = 'INTERVENIDO';
    case BAJA_NO_SIRVE = 'BAJA_NO_SIRVE';
    case REPARADO = 'REPARADO';

    public function label(): string
    {
        return match ($this) {
            self::POR_SOPLAR => 'Por soplar',
            self::PENDIENTE_GARANTIA => 'Pendiente garantía',
            self::POR_REACONDICIONAR => 'Por reacondicionar',
            self::COMPLETADO => 'Completado',
            self::INTERVENIDO => 'Intervenido',
            self::BAJA_NO_SIRVE => 'Baja (No sirve)',
            self::REPARADO => 'Reparado',
        };
    }

    public function esTerminal(): bool
    {
        return in_array(
            $this,
            [self::COMPLETADO, self::INTERVENIDO, self::BAJA_NO_SIRVE, self::REPARADO],
            true,
        );
    }

    /**
     * Posición en el flujo operativo de taller (menor = más pendiente).
     * Se usa para ordenar la columna Estado por proceso y no alfabéticamente.
     */
    public function ordenFlujo(): int
    {
        return match ($this) {
            self::POR_SOPLAR => 1,
            self::PENDIENTE_GARANTIA => 2,
            self::POR_REACONDICIONAR => 3,
            self::COMPLETADO => 4,
            self::INTERVENIDO => 5,
            self::REPARADO => 6,
            self::BAJA_NO_SIRVE => 7,
        };
    }
}
