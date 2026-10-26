<?php

use App\Enums\ClasificacionPortatilEnum;
use App\Enums\EstadoEquipoEnum;
use App\Enums\TipoGestionEnum;
use App\Enums\UbicacionDestinoEnum;
use App\Models\Equipo;
use App\Models\GarantiaPortatil;
use App\Models\InventarioGeneral;
use App\Models\SopladoRegistro;
use App\Models\User;
use App\Support\InventarioE2eFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('restablece las 15 placas reservadas a su estado virgen y elimina sus hijos', function () {
    $analista = User::factory()->create();

    // CPU con cadena completa (diagnóstico + soplado) en AF-100001.
    $cpu = InventarioGeneral::factory()->create([
        'placa_id' => 'AF-100001',
        'estado_actual' => EstadoEquipoEnum::COMPLETADO,
        'intervenido' => true,
        'ubicacion_actual' => UbicacionDestinoEnum::ALMACEN,
        'analista_intervencion' => $analista->id,
        'modulo_intervencion' => 'soplado',
        'fecha_intervencion' => now(),
    ]);

    $equipo = Equipo::create([
        'inventario_general_id' => $cpu->id,
        'placa_id' => 'AF-100001',
        'serial' => $cpu->serial,
        'marca' => $cpu->marca,
        'referencia' => $cpu->referencia,
        'descripcion' => 'CPU',
        'num_traslado' => $cpu->num_traslado,
        'tipo_gestion' => TipoGestionEnum::NOVEDAD,
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => UbicacionDestinoEnum::ALMACEN,
        'estado' => EstadoEquipoEnum::COMPLETADO,
        'analista_id' => $analista->id,
    ]);

    SopladoRegistro::create([
        'equipo_id' => $equipo->id,
        'energiza' => true,
        'da_video' => true,
        'acceso_bios' => true,
        'tipo_suciedad' => 'polvo',
        'aplica_pasta_termica' => true,
        'foto_ruta' => 'fotos/soplado/AF-100001-interior.jpg',
        'ubicacion_destino' => UbicacionDestinoEnum::ALMACEN,
        'analista_id' => $analista->id,
    ]);

    // Portátil intervenido en AF-100101 (bloque de portátiles reservados).
    $portatil = InventarioGeneral::factory()->create([
        'placa_id' => 'AF-100101',
        'descripcion' => 'PORTATIL',
        'estado_actual' => EstadoEquipoEnum::INTERVENIDO,
        'intervenido' => true,
        'ubicacion_actual' => UbicacionDestinoEnum::ALMACEN,
        'analista_intervencion' => $analista->id,
        'modulo_intervencion' => 'portatiles',
        'fecha_intervencion' => now(),
    ]);

    GarantiaPortatil::create([
        'inventario_general_id' => $portatil->id,
        'placa_id' => 'AF-100101',
        'serial' => $portatil->serial,
        'marca' => $portatil->marca,
        'referencia' => $portatil->referencia,
        'num_traslado' => $portatil->num_traslado,
        'clasificacion' => ClasificacionPortatilEnum::GARANTIA,
        'test_lenovo' => true,
        'ubicacion_destino' => UbicacionDestinoEnum::ALMACEN,
        'estado' => EstadoEquipoEnum::INTERVENIDO,
        'analista_id' => $analista->id,
    ]);

    // Placa AJENA al rango reservado: no debe tocarse.
    $ajena = InventarioGeneral::factory()->create(['placa_id' => 'AF-999999']);

    $this->artisan('e2e:reset-fixtures')->assertSuccessful();

    // Hijos eliminados.
    $this->assertDatabaseMissing('equipos', ['placa_id' => 'AF-100001']);
    $this->assertDatabaseMissing('soplado_registros', ['equipo_id' => $equipo->id]);
    $this->assertDatabaseMissing('garantia_portatil', ['placa_id' => 'AF-100101']);

    // Las placas reservadas quedan vírgenes.
    foreach (InventarioE2eFixtures::placas() as $placa) {
        $this->assertDatabaseHas('inventario_general', [
            'placa_id' => $placa,
            'estado_actual' => null,
            'intervenido' => false,
            'ubicacion_actual' => 'ALMACEN',
            'analista_intervencion' => null,
            'modulo_intervencion' => null,
        ]);
    }

    // El portátil reservado conserva su identidad (descripcion) tras el reset.
    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-100101',
        'descripcion' => 'PORTATIL',
        'estado_actual' => null,
    ]);

    // La placa ajena conserva su estado.
    $this->assertDatabaseHas('inventario_general', ['placa_id' => 'AF-999999', 'id' => $ajena->id]);
});

it('recrea las placas reservadas que no existían', function () {
    // Sin sembrar ninguna placa reservada, el comando debe recrearlas todas.
    $this->artisan('e2e:reset-fixtures')->assertSuccessful();

    $this->assertDatabaseCount('inventario_general', 25);

    foreach (InventarioE2eFixtures::placas() as $placa) {
        $this->assertDatabaseHas('inventario_general', [
            'placa_id' => $placa,
            'estado_actual' => null,
            'intervenido' => false,
            'analista_intervencion' => null,
            'modulo_intervencion' => null,
        ]);
    }
});

it('es idempotente al ejecutarse dos veces seguidas', function () {
    $analista = User::factory()->create();

    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-100001',
        'estado_actual' => EstadoEquipoEnum::COMPLETADO,
        'intervenido' => true,
        'analista_intervencion' => $analista->id,
    ]);

    $this->artisan('e2e:reset-fixtures')->assertSuccessful();
    $this->artisan('e2e:reset-fixtures')->assertSuccessful();

    $this->assertDatabaseCount('inventario_general', 25);
    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-100001',
        'estado_actual' => null,
        'intervenido' => false,
    ]);
});

it('está bloqueado en producción', function () {
    $originalEnv = $this->app['env'];
    $this->app['env'] = 'production';

    try {
        $this->artisan('e2e:reset-fixtures')->assertFailed();
    } finally {
        $this->app['env'] = $originalEnv;
    }
});
