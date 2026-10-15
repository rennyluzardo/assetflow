<?php

namespace App\Services\Equipos;

use App\Enums\EstadoEquipoEnum;
use App\Enums\TipoGestionEnum;
use App\Enums\UbicacionDestinoEnum;
use Illuminate\Validation\ValidationException;

final class CpuStateMachineService
{
    /**
     * Resuelve el estado terminal o intermedio a partir de las respuestas clínicas.
     */
    public function resolverEstado(TipoGestionEnum $tipo, array $respuestas): EstadoEquipoEnum
    {
        return match ($tipo) {
            TipoGestionEnum::NOVEDAD => $this->resolverNovedad($respuestas),
            TipoGestionEnum::DIAGNOSTICO => $this->resolverDiagnostico($respuestas),
            TipoGestionEnum::INTERVENCION => $this->resolverIntervencion($respuestas),
        };
    }

    /**
     * @return UbicacionDestinoEnum[]
     */
    public function destinosPermitidos(TipoGestionEnum $tipo, array $respuestas): array
    {
        return match ($tipo) {
            TipoGestionEnum::NOVEDAD => $this->destinosNovedad($respuestas),
            TipoGestionEnum::DIAGNOSTICO => $this->destinosDiagnostico($respuestas),
            TipoGestionEnum::INTERVENCION => $this->destinosIntervencion(),
        };
    }

    /**
     * Valida estrictamente que la ubicación de destino sea coherente con la rama.
     */
    public function validarDestino(TipoGestionEnum $tipo, array $respuestas, UbicacionDestinoEnum $destino): void
    {
        $permitidos = $this->destinosPermitidos($tipo, $respuestas);

        if (! in_array($destino, $permitidos, true)) {
            $this->rechazarDestino($destino, $permitidos);
        }
    }

    /**
     * Resuelve la transición de cierre de los estados intermedios
     * (PENDIENTE_GARANTIA y POR_REACONDICIONAR).
     */
    public function resolverCierre(EstadoEquipoEnum $estadoActual, array $respuestas): EstadoEquipoEnum
    {
        return match ($estadoActual) {
            EstadoEquipoEnum::PENDIENTE_GARANTIA => EstadoEquipoEnum::COMPLETADO,
            EstadoEquipoEnum::POR_REACONDICIONAR => $this->respuestaBooleana($respuestas, 'requiere_soplado_o_pasta')
                ? EstadoEquipoEnum::POR_SOPLAR
                : EstadoEquipoEnum::INTERVENIDO,
            default => throw ValidationException::withMessages([
                'estado' => "El estado {$estadoActual->value} no admite una transición de cierre.",
            ]),
        };
    }

    /**
     * @return UbicacionDestinoEnum[]
     */
    public function destinosPermitidosCierre(EstadoEquipoEnum $estadoActual): array
    {
        return match ($estadoActual) {
            EstadoEquipoEnum::PENDIENTE_GARANTIA => [UbicacionDestinoEnum::DBAN, UbicacionDestinoEnum::ALMACEN],
            EstadoEquipoEnum::POR_REACONDICIONAR => [UbicacionDestinoEnum::SALA_DE_GARANTIA],
            default => [],
        };
    }

    public function validarDestinoCierre(EstadoEquipoEnum $estadoActual, UbicacionDestinoEnum $destino): void
    {
        $permitidos = $this->destinosPermitidosCierre($estadoActual);

        if (! in_array($destino, $permitidos, true)) {
            $this->rechazarDestino($destino, $permitidos);
        }
    }

    /**
     * Estado final al cerrar desde el módulo /soplado según el origen del registro.
     */
    public function estadoFinalSoplado(TipoGestionEnum $tipo): EstadoEquipoEnum
    {
        return in_array($tipo, [TipoGestionEnum::NOVEDAD, TipoGestionEnum::DIAGNOSTICO], true)
            ? EstadoEquipoEnum::COMPLETADO
            : EstadoEquipoEnum::INTERVENIDO;
    }

    /**
     * @return UbicacionDestinoEnum[]
     */
    public function destinosPermitidosSoplado(): array
    {
        return [UbicacionDestinoEnum::ALMACEN, UbicacionDestinoEnum::DBAN];
    }

    public function validarDestinoSoplado(UbicacionDestinoEnum $destino): void
    {
        if (! in_array($destino, $this->destinosPermitidosSoplado(), true)) {
            $this->rechazarDestino($destino, $this->destinosPermitidosSoplado());
        }
    }

    private function resolverNovedad(array $r): EstadoEquipoEnum
    {
        return $this->respuestaBooleana($r, 'requiere_soplado_o_pasta')
            ? EstadoEquipoEnum::POR_SOPLAR
            : EstadoEquipoEnum::COMPLETADO;
    }

    private function destinosNovedad(array $r): array
    {
        return $this->respuestaBooleana($r, 'requiere_soplado_o_pasta')
            ? [UbicacionDestinoEnum::SALA_DE_GARANTIA]
            : [UbicacionDestinoEnum::DBAN, UbicacionDestinoEnum::ALMACEN];
    }

    private function resolverDiagnostico(array $r): EstadoEquipoEnum
    {
        if (! $this->respuestaBooleana($r, 'esta_funcional')) {
            return $this->respuestaBooleana($r, 'tiene_garantia')
                ? EstadoEquipoEnum::PENDIENTE_GARANTIA
                : EstadoEquipoEnum::BAJA_NO_SIRVE;
        }

        if ($this->respuestaBooleana($r, 'es_obsoleta')) {
            return EstadoEquipoEnum::BAJA_NO_SIRVE;
        }

        return $this->respuestaBooleana($r, 'requiere_soplado_o_pasta')
            ? EstadoEquipoEnum::POR_SOPLAR
            : EstadoEquipoEnum::COMPLETADO;
    }

    private function destinosDiagnostico(array $r): array
    {
        if (! $this->respuestaBooleana($r, 'esta_funcional')) {
            return $this->respuestaBooleana($r, 'tiene_garantia')
                ? [UbicacionDestinoEnum::SALA_DE_GARANTIA]
                : [UbicacionDestinoEnum::SALA_DE_BAJA];
        }

        if ($this->respuestaBooleana($r, 'es_obsoleta')) {
            return [UbicacionDestinoEnum::SALA_DE_BAJA];
        }

        return $this->respuestaBooleana($r, 'requiere_soplado_o_pasta')
            ? [UbicacionDestinoEnum::SALA_DE_GARANTIA]
            : [UbicacionDestinoEnum::DBAN, UbicacionDestinoEnum::ALMACEN];
    }

    private function resolverIntervencion(array $r): EstadoEquipoEnum
    {
        if (! $this->respuestaBooleana($r, 'hay_pieza')) {
            return EstadoEquipoEnum::POR_REACONDICIONAR;
        }

        return $this->respuestaBooleana($r, 'requiere_soplado_o_pasta')
            ? EstadoEquipoEnum::POR_SOPLAR
            : EstadoEquipoEnum::INTERVENIDO;
    }

    private function destinosIntervencion(): array
    {
        return [UbicacionDestinoEnum::SALA_DE_GARANTIA];
    }

    private function respuestaBooleana(array $respuestas, string $clave): bool
    {
        if (! array_key_exists($clave, $respuestas)) {
            throw ValidationException::withMessages([
                $clave => "La respuesta \"{$clave}\" es obligatoria para este tipo de gestión.",
            ]);
        }

        return (bool) $respuestas[$clave];
    }

    /**
     * @param  UbicacionDestinoEnum[]  $permitidos
     */
    private function rechazarDestino(UbicacionDestinoEnum $destino, array $permitidos): void
    {
        $lista = implode(', ', array_map(
            fn (UbicacionDestinoEnum $d) => $d->value,
            $permitidos,
        ));

        throw ValidationException::withMessages([
            'ubicacion_destino' => "La ubicación \"{$destino->value}\" no es válida para este flujo. Permitidas: {$lista}.",
        ]);
    }
}
