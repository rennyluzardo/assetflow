<?php

namespace App\Enums;

/**
 * Sedes físicas del taller (ubicaciones con identificador único). Definen tanto
 * la sede de un operario (`users.sede`) como las zonas de origen/destino de un
 * traslado (`traslados.zona_origen` / `traslados.zona_destino`).
 */
enum SedeOperativaEnum: string
{
    case BOGOTA = 'BOGOTA';
    case MEDELLIN = 'MEDELLIN';

    public function label(): string
    {
        return match ($this) {
            self::BOGOTA => 'Bogotá',
            self::MEDELLIN => 'Medellín',
        };
    }

    /**
     * Resuelve una sede operativa a partir de la ubicación física de origen de
     * un lote (cadena libre del cargue masivo). Si el texto menciona Medellín se
     * asigna esa sede; en cualquier otro caso (incluido null/vacío) se asume la
     * sede principal (Bogotá).
     */
    public static function desdeOrigen(?string $origen): self
    {
        $normalizado = mb_strtolower(trim((string) $origen), 'UTF-8');
        $normalizado = strtr($normalizado, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);

        return str_contains($normalizado, strtolower(self::MEDELLIN->value))
            ? self::MEDELLIN
            : self::BOGOTA;
    }
}
