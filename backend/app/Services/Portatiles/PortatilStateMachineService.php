<?php

namespace App\Services\Portatiles;

use App\Enums\CausalBajaEnum;
use App\Enums\ClasificacionPortatilEnum;
use App\Enums\EstadoEquipoEnum;
use App\Enums\GravedadNovedadEnum;
use App\Enums\OrigenDanoGarantiaEnum;
use App\Enums\UbicacionDestinoEnum;
use Illuminate\Validation\ValidationException;

/**
 * Máquina de estados de Portátiles y Garantías.
 *
 * A diferencia de las CPUs (que encadenan varias estaciones), cada laptop vive
 * en un REGISTRO ÚNICO de `garantia_portatil` que se crea con el diagnóstico
 * inicial y se muta en el cierre hacia Almacén General. El destino y el estado
 * se derivan exclusivamente de la Matriz de Clasificación de Laptops; el
 * analista nunca los elige libremente (Regla Global n.º 2).
 */
final class PortatilStateMachineService
{
    /**
     * Resuelve el estado automático a partir de la clasificación y sus sub-campos.
     *
     * @param  array<string, mixed>  $campos
     */
    public function resolverEstado(ClasificacionPortatilEnum $clasificacion, array $campos): EstadoEquipoEnum
    {
        return match ($clasificacion) {
            ClasificacionPortatilEnum::FUNCIONAL => EstadoEquipoEnum::COMPLETADO,
            ClasificacionPortatilEnum::NOVEDAD => $this->resolverNovedad($campos),
            ClasificacionPortatilEnum::GARANTIA => $this->resolverGarantia($campos),
            ClasificacionPortatilEnum::DANO_REPARABLE => $this->resolverDanoReparable($campos),
            ClasificacionPortatilEnum::BAJA => $this->resolverBaja($campos),
        };
    }

    /**
     * Destino obligatorio de la rama (siempre único en portátiles).
     *
     * @param  array<string, mixed>  $campos
     * @return UbicacionDestinoEnum[]
     */
    public function destinosPermitidos(ClasificacionPortatilEnum $clasificacion, array $campos): array
    {
        return match ($clasificacion) {
            ClasificacionPortatilEnum::FUNCIONAL => [UbicacionDestinoEnum::ALMACEN],
            ClasificacionPortatilEnum::NOVEDAD => $this->esNovedadPronunciada($campos)
                ? [UbicacionDestinoEnum::SALA_DE_GARANTIA]
                : [UbicacionDestinoEnum::ALMACEN],
            ClasificacionPortatilEnum::GARANTIA,
            ClasificacionPortatilEnum::DANO_REPARABLE => [UbicacionDestinoEnum::SALA_DE_GARANTIA],
            ClasificacionPortatilEnum::BAJA => [UbicacionDestinoEnum::SALA_DE_BAJA],
        };
    }

    /**
     * @param  array<string, mixed>  $campos
     */
    public function validarDestino(
        ClasificacionPortatilEnum $clasificacion,
        array $campos,
        UbicacionDestinoEnum $destino,
    ): void {
        $permitidos = $this->destinosPermitidos($clasificacion, $campos);

        if (! in_array($destino, $permitidos, true)) {
            $this->rechazarDestino($destino, $permitidos);
        }
    }

    /**
     * Estados intermedios que admiten el Punto de Control de Cierre.
     */
    public function esEstadoCierre(EstadoEquipoEnum $estado): bool
    {
        return in_array(
            $estado,
            [EstadoEquipoEnum::PENDIENTE_GARANTIA, EstadoEquipoEnum::POR_REACONDICIONAR],
            true,
        );
    }

    /**
     * Cierre del registro único: siempre hacia Almacén General.
     */
    public function validarCierre(EstadoEquipoEnum $estadoActual, UbicacionDestinoEnum $destino): void
    {
        if (! $this->esEstadoCierre($estadoActual)) {
            throw ValidationException::withMessages([
                'estado' => "El estado {$estadoActual->value} no admite un cierre de portátil.",
            ]);
        }

        if ($destino !== UbicacionDestinoEnum::ALMACEN) {
            $this->rechazarDestino($destino, [UbicacionDestinoEnum::ALMACEN]);
        }
    }

    public function estadoCierre(): EstadoEquipoEnum
    {
        return EstadoEquipoEnum::INTERVENIDO;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function resolverNovedad(array $c): EstadoEquipoEnum
    {
        $gravedad = $this->requerirEnum($c, 'gravedad_novedad', GravedadNovedadEnum::class);

        if ($gravedad === GravedadNovedadEnum::MINIMA) {
            return EstadoEquipoEnum::COMPLETADO;
        }

        // Pronunciada: más de 1 daño y piezas a solicitar (flujo abierto).
        if ($this->requerirCantidadDanos($c) <= 1) {
            throw ValidationException::withMessages([
                'cantidad_danos' => 'Una novedad pronunciada exige más de 1 daño (cantidad_danos > 1).',
            ]);
        }

        $this->requerirTexto($c, 'piezas_solicitar');

        return EstadoEquipoEnum::POR_REACONDICIONAR;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function resolverGarantia(array $c): EstadoEquipoEnum
    {
        $origen = $this->requerirEnum($c, 'origen_dano_garantia', OrigenDanoGarantiaEnum::class);

        if ($origen === OrigenDanoGarantiaEnum::FABRICA) {
            $this->requerirTexto($c, 'ticket_proveedor');

            return EstadoEquipoEnum::PENDIENTE_GARANTIA;
        }

        // Inducido: se desvía a compra de piezas.
        $this->requerirTexto($c, 'piezas_solicitar');

        return EstadoEquipoEnum::POR_REACONDICIONAR;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function resolverDanoReparable(array $c): EstadoEquipoEnum
    {
        if ($this->requerirCantidadDanos($c) > 3) {
            throw ValidationException::withMessages([
                'cantidad_danos' => 'El daño reparable admite máximo 3 daños (cantidad_danos <= 3).',
            ]);
        }

        $this->requerirTexto($c, 'piezas_solicitar');

        // `partes_fru` es opcional: no todos los portátiles tienen código FRU
        // de Lenovo al momento del diagnóstico físico (Regla de Fase 6).
        return EstadoEquipoEnum::POR_REACONDICIONAR;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function resolverBaja(array $c): EstadoEquipoEnum
    {
        $this->requerirEnum($c, 'causal_baja', CausalBajaEnum::class);

        return EstadoEquipoEnum::BAJA_NO_SIRVE;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function esNovedadPronunciada(array $c): bool
    {
        return $this->requerirEnum($c, 'gravedad_novedad', GravedadNovedadEnum::class)
            === GravedadNovedadEnum::PRONUNCIADA;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function requerirCantidadDanos(array $c): int
    {
        return (int) $this->requerir($c, 'cantidad_danos');
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function requerirTexto(array $c, string $clave): string
    {
        return trim((string) $this->requerir($c, $clave));
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T
     */
    private function requerirEnum(array $c, string $clave, string $enum): \BackedEnum
    {
        $valor = $this->requerir($c, $clave);
        $caso = $enum::tryFrom($valor);

        if ($caso === null) {
            throw ValidationException::withMessages([
                $clave => "El valor \"{$valor}\" no es válido para {$clave}.",
            ]);
        }

        return $caso;
    }

    /**
     * @param  array<string, mixed>  $c
     */
    private function requerir(array $c, string $clave): mixed
    {
        if (! array_key_exists($clave, $c) || $c[$clave] === null || $c[$clave] === '') {
            throw ValidationException::withMessages([
                $clave => "El campo \"{$clave}\" es obligatorio para esta clasificación.",
            ]);
        }

        return $c[$clave];
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
