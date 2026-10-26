<?php

namespace App\Enums;

enum UbicacionDestinoEnum: string
{
    case DBAN = 'DBAN';
    case ALMACEN = 'ALMACEN';
    case SALA_DE_GARANTIA = 'SALA_DE_GARANTIA';
    case SALA_DE_BAJA = 'SALA_DE_BAJA';

    public function label(): string
    {
        return match ($this) {
            self::DBAN => 'Dban',
            self::ALMACEN => 'Almacén',
            self::SALA_DE_GARANTIA => 'Sala de Garantía',
            self::SALA_DE_BAJA => 'Sala de Baja',
        };
    }

    /**
     * Posición en el recorrido físico del activo (menor = más pendiente de trabajo).
     * Se usa para ordenar la columna Ubicación por proceso y no alfabéticamente.
     */
    public function ordenFlujo(): int
    {
        return match ($this) {
            self::SALA_DE_GARANTIA => 1,
            self::DBAN => 2,
            self::ALMACEN => 3,
            self::SALA_DE_BAJA => 4,
        };
    }
}
