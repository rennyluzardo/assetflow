<?php

namespace App\Enums;

/**
 * Ciclo de vida de un traslado: se abre sin origen (hasta que entra el primer
 * equipo), se despacha hacia destino y se cierra cuando el destino confirma la
 * recepción (total o parcial).
 */
enum TrasladoEstadoEnum: string
{
    case ABIERTO = 'ABIERTO';
    case EN_TRANSITO = 'EN_TRANSITO';
    case RECIBIDO = 'RECIBIDO';

    public function label(): string
    {
        return match ($this) {
            self::ABIERTO => 'Abierto',
            self::EN_TRANSITO => 'En tránsito',
            self::RECIBIDO => 'Recibido',
        };
    }

    /**
     * Valores admitidos por la regla `Rule::in` del FormRequest de listado.
     *
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_map(fn (self $estado): string => $estado->value, self::cases());
    }
}
