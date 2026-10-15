<?php

namespace Database\Seeders;

use App\Enums\EstadoEquipoEnum;
use App\Enums\TipoGestionEnum;
use App\Enums\UbicacionDestinoEnum;
use App\Models\Equipo;
use App\Models\GarantiaPortatil;
use App\Models\InventarioGeneral;
use App\Models\SopladoRegistro;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InventarioMockSeeder extends Seeder
{
    private User $analista;

    private User $supervisor;

    public function run(): void
    {
        $this->analista = $this->usuario('analista@assetflow.test', 'Analista de Garantías');
        $this->supervisor = $this->usuario('supervisor@assetflow.test', 'Supervisor de Garantías');

        $this->seedPendientes();
        $this->seedCpus();
        $this->seedPortatiles();
    }

    private function usuario(string $email, string $name): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')],
        );
    }

    private function inventario(string $placa, string $serial, string $marca, string $referencia, string $descripcion, string $traslado): InventarioGeneral
    {
        return InventarioGeneral::create([
            'placa_id' => $placa,
            'serial' => $serial,
            'marca' => $marca,
            'referencia' => $referencia,
            'descripcion' => $descripcion,
            'zona_actual' => 'Almacén',
            'ubicacion_actual' => UbicacionDestinoEnum::ALMACEN,
            'num_traslado' => $traslado,
            'estado_actual' => null,
            'intervenido' => false,
            'usuario_cargue' => $this->supervisor->id,
        ]);
    }

    private function seedPendientes(): void
    {
        $pendientes = [
            ['AF-100001', 'SN-100001', 'Dell', 'OptiPlex 7010', 'TR-1001'],
            ['AF-100005', 'SN-100005', 'HP', 'ProDesk 600 G4', 'TR-1002'],
            ['AF-100008', 'SN-100008', 'Dell', 'OptiPlex 7050', 'TR-1003'],
            ['AF-100011', 'SN-100011', 'Lenovo', 'ThinkCentre M720', 'TR-1004'],
        ];

        foreach ($pendientes as [$placa, $serial, $marca, $referencia, $traslado]) {
            $this->inventario($placa, $serial, $marca, $referencia, 'CPU', $traslado);
        }
    }

    private function seedCpus(): void
    {
        $novedadSoplado = $this->inventario('AF-100002', 'SN-100002', 'Dell', 'OptiPlex 7010', 'CPU', 'TR-1001');
        $this->registrarEquipo($novedadSoplado, [
            'tipo_gestion' => TipoGestionEnum::NOVEDAD,
            'requiere_soplado_o_pasta' => true,
            'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
            'observaciones' => 'Equipo con acumulación de polvo, requiere soplado.',
        ], EstadoEquipoEnum::POR_SOPLAR);

        $novedadLimpio = $this->inventario('AF-100003', 'SN-100003', 'HP', 'ProDesk 400 G5', 'CPU', 'TR-1001');
        $this->registrarEquipo($novedadLimpio, [
            'tipo_gestion' => TipoGestionEnum::NOVEDAD,
            'requiere_soplado_o_pasta' => false,
            'ubicacion_destino' => UbicacionDestinoEnum::DBAN,
            'observaciones' => 'Equipo limpio, listo para borrado seguro.',
        ], EstadoEquipoEnum::COMPLETADO);

        $novedadSopladoCerrado = $this->inventario('AF-100004', 'SN-100004', 'Dell', 'OptiPlex 7050', 'CPU', 'TR-1001');
        $equipoSoplado = $this->registrarEquipo($novedadSopladoCerrado, [
            'tipo_gestion' => TipoGestionEnum::NOVEDAD,
            'requiere_soplado_o_pasta' => true,
            'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
        ], EstadoEquipoEnum::POR_SOPLAR);
        $this->registrarSoplado($equipoSoplado, UbicacionDestinoEnum::ALMACEN, EstadoEquipoEnum::COMPLETADO);

        $bajaNoSirve = $this->inventario('AF-100006', 'SN-100006', 'HP', 'ProDesk 600 G4', 'CPU', 'TR-1002');
        $this->registrarEquipo($bajaNoSirve, [
            'tipo_gestion' => TipoGestionEnum::DIAGNOSTICO,
            'esta_funcional' => false,
            'tiene_garantia' => false,
            'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_BAJA,
            'observaciones' => 'Tarjeta madre dañada y sin garantía vigente.',
        ], EstadoEquipoEnum::BAJA_NO_SIRVE);

        $pendienteGarantia = $this->inventario('AF-100007', 'SN-100007', 'Lenovo', 'ThinkCentre M720', 'CPU', 'TR-1002');
        $this->registrarEquipo($pendienteGarantia, [
            'tipo_gestion' => TipoGestionEnum::DIAGNOSTICO,
            'esta_funcional' => false,
            'tiene_garantia' => true,
            'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
            'observaciones' => 'Falla de fuente, en espera de respuesta del proveedor.',
        ], EstadoEquipoEnum::PENDIENTE_GARANTIA);

        $porReacondicionar = $this->inventario('AF-100009', 'SN-100009', 'Dell', 'OptiPlex 7050', 'CPU', 'TR-1003');
        $this->registrarEquipo($porReacondicionar, [
            'tipo_gestion' => TipoGestionEnum::INTERVENCION,
            'hay_pieza' => false,
            'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
            'observaciones' => 'Requiere disco de 512GB aún no disponible en stock.',
        ], EstadoEquipoEnum::POR_REACONDICIONAR);

        $intervenidoSoplado = $this->inventario('AF-100010', 'SN-100010', 'HP', 'ProDesk 600 G4', 'CPU', 'TR-1003');
        $equipoIntervenido = $this->registrarEquipo($intervenidoSoplado, [
            'tipo_gestion' => TipoGestionEnum::INTERVENCION,
            'hay_pieza' => true,
            'requiere_soplado_o_pasta' => true,
            'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
        ], EstadoEquipoEnum::POR_SOPLAR);
        $this->registrarSoplado($equipoIntervenido, UbicacionDestinoEnum::DBAN, EstadoEquipoEnum::INTERVENIDO);

        $diagnosticoSoplado = $this->inventario('AF-100012', 'SN-100012', 'Lenovo', 'ThinkCentre M720', 'CPU', 'TR-1004');
        $this->registrarEquipo($diagnosticoSoplado, [
            'tipo_gestion' => TipoGestionEnum::DIAGNOSTICO,
            'esta_funcional' => true,
            'es_obsoleta' => false,
            'requiere_soplado_o_pasta' => true,
            'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
        ], EstadoEquipoEnum::POR_SOPLAR);
    }

    private function seedPortatiles(): void
    {
        $portatilCompletado = $this->inventario('AF-100101', 'SN-100101', 'Lenovo', 'ThinkPad T14', 'PORTATIL', 'TR-1001');
        $this->registrarPortatil($portatilCompletado, 'DIAGNOSTICO_FUNCIONAL', 'SD-100101', 'TICKET-0001', EstadoEquipoEnum::COMPLETADO, UbicacionDestinoEnum::ALMACEN);

        $portatilReparado = $this->inventario('AF-100102', 'SN-100102', 'Lenovo', 'ThinkPad X1 Carbon', 'PORTATIL', 'TR-1002');
        $this->registrarPortatil($portatilReparado, 'REPARACION_GARANTIA_PROVEEDOR', 'SD-100102', 'TICKET-0002', EstadoEquipoEnum::REPARADO, UbicacionDestinoEnum::ALMACEN);

        $portatilBaja = $this->inventario('AF-100103', 'SN-100103', 'Dell', 'Latitude 7420', 'PORTATIL', 'TR-1003');
        $this->registrarPortatil($portatilBaja, 'INTERVENCION_INTERNA', 'SD-100103', null, EstadoEquipoEnum::BAJA_NO_SIRVE, UbicacionDestinoEnum::SALA_DE_BAJA);
    }

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
            'fecha_intervencion' => now(),
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
            'fecha_intervencion' => now(),
            'intervenido' => true,
        ]);
    }

    private function registrarPortatil(InventarioGeneral $inventario, string $tipoGestion, string $serialDisco, ?string $ticket, EstadoEquipoEnum $estado, UbicacionDestinoEnum $destino): void
    {
        GarantiaPortatil::create([
            'inventario_general_id' => $inventario->id,
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'marca' => $inventario->marca,
            'referencia' => $inventario->referencia,
            'num_traslado' => $inventario->num_traslado,
            'tipo_gestion' => $tipoGestion,
            'test_lenovo' => true,
            'serial_disco' => $serialDisco,
            'ticket_proveedor' => $ticket,
            'parte_fru' => 'FRU-00X000',
            'piezas_solicitar' => 'Teclado, batería',
            'ubicacion_destino' => $destino,
            'estado' => $estado,
            'observaciones' => 'Diagnóstico portátil completado.',
            'analista_id' => $this->analista->id,
        ]);

        $inventario->update([
            'estado_actual' => $estado,
            'ubicacion_actual' => $destino,
            'analista_intervencion' => $this->analista->id,
            'modulo_intervencion' => 'portatiles',
            'fecha_intervencion' => now(),
            'intervenido' => true,
        ]);
    }
}
