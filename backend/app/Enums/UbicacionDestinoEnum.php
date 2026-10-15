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
}
