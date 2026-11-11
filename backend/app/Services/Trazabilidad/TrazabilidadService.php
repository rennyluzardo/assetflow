<?php

namespace App\Services\Trazabilidad;

use App\Models\Equipo;
use App\Models\GarantiaPortatil;
use App\Models\InventarioGeneral;
use App\Models\SopladoRegistro;
use Illuminate\Validation\ValidationException;

/**
 * Hoja de vida de un activo: ficha maestra + eventos cronológicos unificados.
 *
 * Módulo 100 % de solo lectura. Busca por `placa_id` o `serial` y consolida los
 * eventos de las cuatro estaciones (`inventario_general`, `equipos`,
 * `soplado_registros` y `garantia_portatil`) en una única línea de tiempo.
 */
final class TrazabilidadService
{
    /**
     * @return array{activo: array<string, mixed>, eventos: list<array<string, mixed>>}
     */
    public function consultar(string $q): array
    {
        $inventario = InventarioGeneral::query()
            ->where('placa_id', $q)
            ->orWhere('serial', $q)
            ->first();

        if (! $inventario) {
            throw ValidationException::withMessages([
                'q' => 'No se encontró ningún activo con esa placa o serial.',
            ]);
        }

        $eventos = [];
        $this->agregarIngreso($eventos, $inventario);
        $this->agregarEquipos($eventos, $inventario);
        $this->agregarSoplados($eventos, $inventario);
        $this->agregarPortatiles($eventos, $inventario);

        // Línea de tiempo cronológica (más antiguo primero).
        usort(
            $eventos,
            fn (array $a, array $b): int => $a['fecha']->getTimestamp() <=> $b['fecha']->getTimestamp(),
        );

        return [
            'activo' => [
                'placa_id' => $inventario->placa_id,
                'serial' => $inventario->serial,
                'marca' => $inventario->marca,
                'referencia' => $inventario->referencia,
                'descripcion' => $inventario->descripcion,
                'zona_actual' => $inventario->zona_actual,
                'num_traslado' => $inventario->num_traslado,
                'ubicacion_actual' => $inventario->ubicacion_actual?->value,
                'estado_actual' => $inventario->estado_actual?->value,
                'intervenido' => $inventario->intervenido,
                'fecha_intervencion' => $inventario->fecha_intervencion?->toIso8601String(),
            ],
            'eventos' => array_map(
                fn (array $evento): array => [
                    'tipo' => $evento['tipo'],
                    'estacion' => $evento['estacion'],
                    'fecha' => $evento['fecha']->toIso8601String(),
                    'analista' => $evento['analista'],
                    'ubicacion' => $evento['ubicacion'],
                    'estado' => $evento['estado'],
                    'foto' => $evento['foto'],
                    'detalle' => $evento['detalle'],
                    'observaciones' => $evento['observaciones'],
                ],
                $eventos,
            ),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $eventos
     */
    private function agregarIngreso(array &$eventos, InventarioGeneral $inventario): void
    {
        $eventos[] = [
            'tipo' => 'inventario',
            'estacion' => 'Inventario',
            'fecha' => $inventario->created_at,
            'analista' => $inventario->usuarioCargue?->name,
            'ubicacion' => $inventario->ubicacion_actual?->value,
            'estado' => null,
            'foto' => null,
            'detalle' => 'Ingreso al inventario · Traslado '.$inventario->num_traslado,
            'observaciones' => null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $eventos
     */
    private function agregarEquipos(array &$eventos, InventarioGeneral $inventario): void
    {
        $equipos = Equipo::where('placa_id', $inventario->placa_id)
            ->orderBy('created_at')
            ->get();

        foreach ($equipos as $equipo) {
            $eventos[] = [
                'tipo' => 'equipos',
                'estacion' => 'CPU',
                'fecha' => $equipo->created_at,
                'analista' => $equipo->analista?->name,
                'ubicacion' => $equipo->ubicacion_destino?->value,
                'estado' => $equipo->estado?->value,
                'foto' => $equipo->foto_equipo,
                'detalle' => $equipo->tipo_gestion->label(),
                'observaciones' => $equipo->observaciones,
            ];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $eventos
     */
    private function agregarSoplados(array &$eventos, InventarioGeneral $inventario): void
    {
        $equipoIds = Equipo::where('placa_id', $inventario->placa_id)->pluck('id');

        $soplados = SopladoRegistro::whereIn('equipo_id', $equipoIds)
            ->orderBy('created_at')
            ->get();

        foreach ($soplados as $soplado) {
            $eventos[] = [
                'tipo' => 'soplado',
                'estacion' => 'Soplado',
                'fecha' => $soplado->created_at,
                'analista' => $soplado->analista?->name,
                'ubicacion' => $soplado->ubicacion_destino?->value,
                'estado' => null,
                'foto' => $soplado->foto_ruta,
                'detalle' => 'Limpieza y despacho final',
                'observaciones' => $this->notaTecnicaSoplado($soplado),
            ];
        }
    }

    /**
     * El registro único de portátil se representa con su último cambio
     * (`updated_at`): para un registro abierto coincide con el diagnóstico y
     * para uno cerrado refleja el momento del cierre hacia Almacén.
     *
     * @param  list<array<string, mixed>>  $eventos
     */
    private function agregarPortatiles(array &$eventos, InventarioGeneral $inventario): void
    {
        $registros = GarantiaPortatil::where('placa_id', $inventario->placa_id)
            ->orderBy('updated_at')
            ->get();

        foreach ($registros as $registro) {
            $detalle = 'Portátil · '.$registro->clasificacion->label();

            if ($registro->reparado_por) {
                $detalle .= ' · Reparado por '.$registro->reparado_por;
            }

            $eventos[] = [
                'tipo' => 'portatil',
                'estacion' => 'Portátil',
                'fecha' => $registro->updated_at,
                'analista' => $registro->analista?->name,
                'ubicacion' => $registro->ubicacion_destino?->value,
                'estado' => $registro->estado?->value,
                'foto' => $registro->foto_evidencia,
                'detalle' => $detalle,
                'observaciones' => $registro->observaciones,
            ];
        }
    }

    /**
     * `soplado_registros` no tiene una columna libre de comentarios: la nota
     * técnica del evento se deriva de los hallazgos de la inspección (estado de
     * arranque/video/BIOS, tipo de suciedad y aplicación de pasta térmica).
     */
    private function notaTecnicaSoplado(SopladoRegistro $soplado): string
    {
        $suciedad = match ($soplado->tipo_suciedad) {
            'polvo' => 'Polvo',
            'cucarachas' => 'Cucarachas',
            'polvo_cucarachas' => 'Polvo y cucarachas',
            'ninguna' => 'Ninguna',
            default => $soplado->tipo_suciedad,
        };

        return implode(' · ', [
            'Energiza: '.($soplado->energiza ? 'Sí' : 'No'),
            'Da video: '.($soplado->da_video ? 'Sí' : 'No'),
            'Acceso BIOS: '.($soplado->acceso_bios ? 'Sí' : 'No'),
            'Suciedad: '.$suciedad,
            'Pasta térmica: '.($soplado->aplica_pasta_termica ? 'Aplicada' : 'No aplicada'),
        ]);
    }
}
