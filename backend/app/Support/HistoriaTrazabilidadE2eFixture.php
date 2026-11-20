<?php

namespace App\Support;

use App\Enums\EstadoEquipoEnum;
use App\Enums\ModuloIntervencionEnum;
use App\Enums\TipoGestionEnum;
use App\Enums\UbicacionDestinoEnum;
use App\Models\Equipo;
use App\Models\InventarioGeneral;
use App\Models\SopladoRegistro;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Hoja de vida completa (inventario → CPU → soplado) usada por el E2E de
 * trazabilidad. Vive fuera del seed automático para que `migrate:fresh --seed`
 * no rellene inventario; `e2e:reset-fixtures` la recrea si falta.
 */
final class HistoriaTrazabilidadE2eFixture
{
    public const PLACA = 'AF-100016';

    public static function asegurar(): InventarioGeneral
    {
        $existente = InventarioGeneral::where('placa_id', self::PLACA)->first();

        if ($existente) {
            return $existente;
        }

        $analista = self::usuario('cpu@assetflow.local', 'Técnico CPU');
        $cargue = self::usuario('admin@assetflow.local', 'Administrador');

        $inventario = InventarioGeneral::create([
            'placa_id' => self::PLACA,
            'serial' => 'SN-100016',
            'marca' => 'Dell',
            'referencia' => 'OptiPlex 7050',
            'descripcion' => 'CPU',
            'num_traslado' => 'TR-1001',
            'zona_actual' => 'Almacén',
            'ubicacion_actual' => UbicacionDestinoEnum::ALMACEN,
            'estado_actual' => null,
            'intervenido' => false,
            'usuario_cargue' => $cargue->id,
        ]);

        $equipo = Equipo::create([
            'inventario_general_id' => $inventario->id,
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'marca' => $inventario->marca,
            'referencia' => $inventario->referencia,
            'descripcion' => $inventario->descripcion,
            'num_traslado' => $inventario->num_traslado,
            'tipo_gestion' => TipoGestionEnum::NOVEDAD,
            'requiere_soplado_o_pasta' => true,
            'observaciones' => 'Equipo con acumulación de polvo, requiere soplado.',
            'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
            'estado' => EstadoEquipoEnum::POR_SOPLAR,
            'analista_id' => $analista->id,
        ]);

        $inventario->update([
            'estado_actual' => EstadoEquipoEnum::POR_SOPLAR,
            'ubicacion_actual' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
            'analista_intervencion' => $analista->id,
            'modulo_intervencion' => ModuloIntervencionEnum::EQUIPOS->value,
            'fecha_intervencion' => now()->subDays(12),
            'intervenido' => false,
        ]);

        SopladoRegistro::create([
            'equipo_id' => $equipo->id,
            'energiza' => true,
            'da_video' => true,
            'acceso_bios' => true,
            'tipo_suciedad' => 'polvo',
            'aplica_pasta_termica' => true,
            'foto_ruta' => 'fotos/soplado/'.self::PLACA.'-interior.jpg',
            'ubicacion_destino' => UbicacionDestinoEnum::ALMACEN,
            'analista_id' => $analista->id,
        ]);

        $equipo->update([
            'estado' => EstadoEquipoEnum::COMPLETADO,
            'ubicacion_destino' => UbicacionDestinoEnum::ALMACEN,
        ]);

        $inventario->update([
            'estado_actual' => EstadoEquipoEnum::COMPLETADO,
            'ubicacion_actual' => UbicacionDestinoEnum::ALMACEN,
            'analista_intervencion' => $analista->id,
            'modulo_intervencion' => ModuloIntervencionEnum::SOPLADO->value,
            'fecha_intervencion' => now()->subDays(12),
            'intervenido' => true,
        ]);

        return $inventario->fresh();
    }

    private static function usuario(string $email, string $name): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')],
        );
    }
}
