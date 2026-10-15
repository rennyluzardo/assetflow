<?php

namespace App\Services\Equipos;

use App\Enums\EstadoEquipoEnum;
use App\Enums\UbicacionDestinoEnum;
use App\Models\Equipo;
use App\Models\InventarioGeneral;
use Illuminate\Validation\ValidationException;

final class EquipoService
{
    public function buscar(string $placaId): array
    {
        $inventario = InventarioGeneral::where('placa_id', $placaId)->firstOrFail();
        $equipo = Equipo::where('placa_id', $placaId)->latest('id')->first();

        return [
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'referencia' => $inventario->referencia,
            'descripcion' => $inventario->descripcion,
            'num_traslado' => $inventario->num_traslado,
            'ubicacion_actual' => $inventario->ubicacion_actual?->value,
            'estado_actual' => $inventario->estado_actual?->value,
            'equipo_activo' => $equipo
                ? [
                    'id' => $equipo->id,
                    'estado' => $equipo->estado->value,
                    'tipo_gestion' => $equipo->tipo_gestion->value,
                ]
                : null,
        ];
    }

    public function flujoAbierto(string $placaId): Equipo
    {
        $equipo = Equipo::where('placa_id', $placaId)
            ->whereIn('estado', [
                EstadoEquipoEnum::PENDIENTE_GARANTIA->value,
                EstadoEquipoEnum::POR_REACONDICIONAR->value,
            ])
            ->latest('id')
            ->first();

        if (! $equipo) {
            throw ValidationException::withMessages([
                'placa_id' => 'No existe un equipo con un flujo abierto que permita esta transición.',
            ]);
        }

        return $equipo;
    }

    public function registrar(array $datos, EstadoEquipoEnum $estado, UbicacionDestinoEnum $destino, int $analistaId): Equipo
    {
        $inventario = InventarioGeneral::where('placa_id', $datos['placa_id'])->firstOrFail();

        $equipo = Equipo::create([
            'inventario_general_id' => $inventario->id,
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'marca' => $inventario->marca,
            'referencia' => $inventario->referencia,
            'descripcion' => $inventario->descripcion,
            'num_traslado' => $inventario->num_traslado,
            'tipo_gestion' => $datos['tipo_gestion'],
            'requiere_soplado_o_pasta' => $datos['requiere_soplado_o_pasta'] ?? false,
            'esta_funcional' => $datos['esta_funcional'] ?? null,
            'tiene_garantia' => $datos['tiene_garantia'] ?? null,
            'es_obsoleta' => $datos['es_obsoleta'] ?? null,
            'hay_pieza' => $datos['hay_pieza'] ?? null,
            'ubicacion_destino' => $destino,
            'estado' => $estado,
            'foto_equipo' => $datos['foto_equipo'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'analista_id' => $analistaId,
        ]);

        $this->sincronizarInventario($equipo, $analistaId);

        return $equipo->fresh();
    }

    public function transicionar(
        Equipo $equipo,
        EstadoEquipoEnum $estado,
        UbicacionDestinoEnum $destino,
        int $analistaId,
        array $respuestas = [],
    ): Equipo {
        $hayPieza = $equipo->estado === EstadoEquipoEnum::POR_REACONDICIONAR
            ? true
            : $equipo->hay_pieza;

        $equipo->update([
            'requiere_soplado_o_pasta' => $respuestas['requiere_soplado_o_pasta'] ?? $equipo->requiere_soplado_o_pasta ?? false,
            'hay_pieza' => $hayPieza,
            'ubicacion_destino' => $destino,
            'estado' => $estado,
        ]);

        $this->sincronizarInventario($equipo, $analistaId);

        return $equipo->fresh();
    }

    private function sincronizarInventario(Equipo $equipo, int $analistaId): void
    {
        $equipo->inventarioGeneral()->update([
            'estado_actual' => $equipo->estado,
            'ubicacion_actual' => $equipo->ubicacion_destino,
            'analista_intervencion' => $analistaId,
            'modulo_intervencion' => 'equipos',
            'fecha_intervencion' => now(),
            'intervenido' => $equipo->estado->esTerminal(),
        ]);
    }
}
