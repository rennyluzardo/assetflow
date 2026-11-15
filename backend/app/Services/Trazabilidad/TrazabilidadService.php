<?php

namespace App\Services\Trazabilidad;

use App\Models\Equipo;
use App\Models\GarantiaPortatil;
use App\Models\InventarioGeneral;
use App\Models\SopladoRegistro;
use App\Models\TrasladoEquipo;
use Illuminate\Support\Facades\Storage;
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
        $this->agregarTraslados($eventos, $inventario);

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
                'ubicacion_actual' => $inventario->ubicacion_actual,
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
                    'fotos' => $evento['fotos'],
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
            'ubicacion' => $inventario->ubicacion_actual,
            'estado' => null,
            'foto' => null,
            'fotos' => [],
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
                'fotos' => $this->fotosUrls($equipo->foto_equipo),
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
                'fotos' => $this->fotosUrls($soplado->foto_ruta),
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
                'fotos' => $this->fotosUrls(
                    $registro->foto_evidencia,
                    $registro->getAttribute('foto_cierre'),
                ),
                'detalle' => $detalle,
                'observaciones' => $registro->observaciones,
            ];
        }
    }

    /**
     * Historia de traslados del activo: una entrada por cada lote en el que el
     * activo viajó. El estado se deja en `null` para no pintar la insignia de
     * estación (el ciclo ABIERTO/EN_TRANSITO/RECIBIDO no es un estado de taller).
     *
     * @param  list<array<string, mixed>>  $eventos
     */
    private function agregarTraslados(array &$eventos, InventarioGeneral $inventario): void
    {
        $trasladoEquipos = TrasladoEquipo::where('inventario_id', $inventario->id)
            ->with('traslado.despachador')
            ->orderBy('created_at')
            ->get();

        foreach ($trasladoEquipos as $pivote) {
            $traslado = $pivote->traslado;

            if (! $traslado) {
                continue;
            }

            $eventos[] = [
                'tipo' => 'traslado',
                'estacion' => 'Traslado',
                'fecha' => $pivote->created_at,
                'analista' => $traslado->despachador?->name,
                'ubicacion' => $traslado->ubicacion_destino,
                'estado' => null,
                'foto' => null,
                'fotos' => [],
                'detalle' => 'Traslado '.$traslado->codigo.' · '.$traslado->zona_origen.' → '.$traslado->zona_destino,
                'observaciones' => $traslado->observaciones,
            ];
        }
    }

    /**
     * Convierte las rutas de evidencia almacenadas en el disco `public` a URLs
     * accesibles (`/storage/...`), descartando valores nulos o vacíos.
     *
     * @return list<string>
     */
    private function fotosUrls(?string ...$rutas): array
    {
        $rutas = array_filter(
            $rutas,
            fn (?string $ruta): bool => is_string($ruta) && trim($ruta) !== '',
        );

        return array_values(array_unique(array_map(
            fn (string $ruta): string => Storage::disk('public')->url(trim($ruta)),
            $rutas,
        )));
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
