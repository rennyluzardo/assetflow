<?php

namespace Database\Seeders;

use App\Enums\EstadoEquipoEnum;
use App\Enums\TipoGestionEnum;
use App\Enums\UbicacionDestinoEnum;
use App\Models\Equipo;
use App\Models\InventarioGeneral;
use App\Models\SopladoRegistro;
use App\Models\User;
use App\Support\InventarioE2eFixtures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Siembra las placas reservadas para la suite E2E de Playwright.
 *
 * Reserva los bloques AF-100001..AF-100015 (CPUs) y AF-100101..AF-100110
 * (portátiles) en estado "virgen" (pendiente) para que los flujos críticos
 * puedan mutarlas, y una hoja de vida completa en AF-100016 (fuera del rango del
 * comando `e2e:reset-fixtures`) para el flujo de trazabilidad. Los datos de
 * demostración operativa viven en `DemoOperativoSeeder`.
 */
class InventarioMockSeeder extends Seeder
{
    /** Placa con hoja de vida completa para trazabilidad (NO la restablece el reset). */
    private const PLACA_HISTORIA = 'AF-100016';

    private User $analista;

    private User $cargue;

    public function run(): void
    {
        if (InventarioGeneral::where('placa_id', 'AF-100001')->exists()) {
            return;
        }

        $this->analista = $this->usuario('cpu@assetflow.local', 'Técnico CPU');
        $this->cargue = $this->usuario('admin@assetflow.local', 'Administrador');

        $this->seedPendientes();
        $this->seedHistoriaTrazabilidad();
    }

    private function usuario(string $email, string $name): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')],
        );
    }

    /**
     * Crea (o reutiliza) una fila virgen de inventario.
     *
     * @param  array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}  $definicion
     */
    private function inventario(array $definicion): InventarioGeneral
    {
        return InventarioGeneral::create($definicion + [
            'zona_actual' => 'Almacén',
            'ubicacion_actual' => UbicacionDestinoEnum::ALMACEN,
            'estado_actual' => null,
            'intervenido' => false,
            'usuario_cargue' => $this->cargue->id,
        ]);
    }

    private function seedPendientes(): void
    {
        foreach (InventarioE2eFixtures::definiciones() as $definicion) {
            $this->inventario($definicion);
        }
    }

    /**
     * Hoja de vida completa CPU → Soplado para el flujo de trazabilidad.
     */
    private function seedHistoriaTrazabilidad(): void
    {
        $inventario = $this->inventario([
            'placa_id' => self::PLACA_HISTORIA,
            'serial' => 'SN-100016',
            'marca' => 'Dell',
            'referencia' => 'OptiPlex 7050',
            'descripcion' => 'CPU',
            'num_traslado' => 'TR-1001',
        ]);

        $equipo = $this->registrarEquipo($inventario, [
            'tipo_gestion' => TipoGestionEnum::NOVEDAD,
            'requiere_soplado_o_pasta' => true,
            'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
        ], EstadoEquipoEnum::POR_SOPLAR);

        $this->registrarSoplado($equipo, UbicacionDestinoEnum::ALMACEN, EstadoEquipoEnum::COMPLETADO);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function registrarEquipo(InventarioGeneral $inventario, array $datos, EstadoEquipoEnum $estado): Equipo
    {
        $equipo = Equipo::create($datos + [
            'inventario_general_id' => $inventario->id,
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'marca' => $inventario->marca,
            'referencia' => $inventario->referencia,
            'descripcion' => $inventario->descripcion,
            'num_traslado' => $inventario->num_traslado,
            'estado' => $estado,
            'analista_id' => $this->analista->id,
        ]);

        $inventario->update([
            'estado_actual' => $estado,
            'ubicacion_actual' => $datos['ubicacion_destino'],
            'analista_intervencion' => $this->analista->id,
            'modulo_intervencion' => 'equipos',
            'fecha_intervencion' => now()->subDays(12), // hoja de vida pasada: no contamina el "hoy" del dashboard demo
            'intervenido' => $estado->esTerminal(),
        ]);

        return $equipo;
    }

    private function registrarSoplado(Equipo $equipo, UbicacionDestinoEnum $destino, EstadoEquipoEnum $estadoFinal): void
    {
        SopladoRegistro::create([
            'equipo_id' => $equipo->id,
            'energiza' => true,
            'da_video' => true,
            'acceso_bios' => true,
            'tipo_suciedad' => 'polvo',
            'aplica_pasta_termica' => true,
            'foto_ruta' => 'fotos/soplado/'.$equipo->placa_id.'-interior.jpg',
            'ubicacion_destino' => $destino,
            'analista_id' => $this->analista->id,
        ]);

        $equipo->update([
            'estado' => $estadoFinal,
            'ubicacion_destino' => $destino,
        ]);

        $equipo->inventarioGeneral()->update([
            'estado_actual' => $estadoFinal,
            'ubicacion_actual' => $destino,
            'analista_intervencion' => $this->analista->id,
            'modulo_intervencion' => 'soplado',
            'fecha_intervencion' => now()->subDays(12), // hoja de vida pasada: no contamina el "hoy" del dashboard demo
            'intervenido' => true,
        ]);
    }
}
