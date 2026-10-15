<?php

namespace App\Services\Soplado;

use App\Enums\EstadoEquipoEnum;
use App\Enums\UbicacionDestinoEnum;
use App\Models\Equipo;
use App\Models\SopladoRegistro;
use App\Services\Equipos\CpuStateMachineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SopladoService
{
    public function __construct(private readonly CpuStateMachineService $stateMachine) {}

    public function registrar(string $placaId, array $datos, UbicacionDestinoEnum $destino, int $analistaId): SopladoRegistro
    {
        $equipo = Equipo::where('placa_id', $placaId)
            ->where('estado', EstadoEquipoEnum::POR_SOPLAR->value)
            ->latest('id')
            ->first();

        if (! $equipo) {
            throw ValidationException::withMessages([
                'placa_id' => 'El equipo no cuenta con un registro activo en estado POR_SOPLAR.',
            ]);
        }

        $this->stateMachine->validarDestinoSoplado($destino);

        return DB::transaction(function () use ($equipo, $datos, $destino, $analistaId) {
            $registro = SopladoRegistro::create([
                'equipo_id' => $equipo->id,
                'energiza' => $datos['energiza'],
                'da_video' => $datos['da_video'],
                'acceso_bios' => $datos['acceso_bios'],
                'tipo_suciedad' => $datos['tipo_suciedad'],
                'aplica_pasta_termica' => $datos['aplica_pasta_termica'],
                'foto_ruta' => $datos['foto_ruta'],
                'ubicacion_destino' => $destino,
                'analista_id' => $analistaId,
            ]);

            $estadoFinal = $this->stateMachine->estadoFinalSoplado($equipo->tipo_gestion);

            $equipo->update([
                'estado' => $estadoFinal,
                'ubicacion_destino' => $destino,
            ]);

            $equipo->inventarioGeneral()->update([
                'estado_actual' => $estadoFinal,
                'ubicacion_actual' => $destino,
                'analista_intervencion' => $analistaId,
                'modulo_intervencion' => 'soplado',
                'fecha_intervencion' => now(),
                'intervenido' => true,
            ]);

            return $registro->fresh();
        });
    }
}
