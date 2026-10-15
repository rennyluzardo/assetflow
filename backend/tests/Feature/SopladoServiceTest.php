<?php

use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['cpus.registrar', 'soplado.registrar', 'cpus.ver_bitacora'] as $permiso) {
        Permission::findOrCreate($permiso);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->analista = User::factory()->create([
        'name' => 'Operario de Soplado',
        'email' => 'operario.soplado@assetflow.test',
    ]);

    $this->analista->givePermissionTo(['cpus.registrar', 'soplado.registrar', 'cpus.ver_bitacora']);

    Sanctum::actingAs($this->analista);
});

it('cierra un equipo Por soplar de origen Novedad a Completado', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-N', 'usuario_cargue' => $this->analista->id]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-SOP-N',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $this->postJson('/api/soplado', [
        'placa_id' => 'AF-SOP-N',
        'energiza' => true,
        'da_video' => true,
        'acceso_bios' => true,
        'tipo_suciedad' => 'polvo',
        'aplica_pasta_termica' => true,
        'foto_ruta' => 'fotos/soplado/AF-SOP-N.jpg',
        'ubicacion_destino' => 'ALMACEN',
    ])->assertCreated();

    $this->assertDatabaseHas('soplado_registros', ['foto_ruta' => 'fotos/soplado/AF-SOP-N.jpg']);
    $this->assertDatabaseHas('equipos', ['placa_id' => 'AF-SOP-N', 'estado' => 'COMPLETADO']);
    $this->assertDatabaseHas('inventario_general', ['placa_id' => 'AF-SOP-N', 'intervenido' => true]);
});

it('cierra un equipo Por soplar de origen Intervención a Intervenido', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-I', 'usuario_cargue' => $this->analista->id]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-SOP-I',
        'tipo_gestion' => 'INTERVENCION',
        'hay_pieza' => true,
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $this->postJson('/api/soplado', [
        'placa_id' => 'AF-SOP-I',
        'energiza' => true,
        'da_video' => true,
        'acceso_bios' => true,
        'tipo_suciedad' => 'cucarachas',
        'aplica_pasta_termica' => true,
        'foto_ruta' => 'fotos/soplado/AF-SOP-I.jpg',
        'ubicacion_destino' => 'DBAN',
    ])->assertCreated();

    $this->assertDatabaseHas('equipos', ['placa_id' => 'AF-SOP-I', 'estado' => 'INTERVENIDO']);
    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-SOP-I',
        'intervenido' => true,
        'ubicacion_actual' => 'DBAN',
    ]);
});

it('rechaza el soplado de un equipo que no está Por soplar', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-X', 'usuario_cargue' => $this->analista->id]);

    $this->postJson('/api/soplado', [
        'placa_id' => 'AF-SOP-X',
        'energiza' => true,
        'da_video' => true,
        'acceso_bios' => true,
        'tipo_suciedad' => 'polvo',
        'aplica_pasta_termica' => true,
        'foto_ruta' => 'fotos/soplado/AF-SOP-X.jpg',
        'ubicacion_destino' => 'ALMACEN',
    ])->assertStatus(422)->assertJsonValidationErrors('placa_id');
});

it('rechaza el soplado con un destino no permitido', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-D', 'usuario_cargue' => $this->analista->id]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-SOP-D',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $this->postJson('/api/soplado', [
        'placa_id' => 'AF-SOP-D',
        'energiza' => true,
        'da_video' => true,
        'acceso_bios' => true,
        'tipo_suciedad' => 'polvo',
        'aplica_pasta_termica' => true,
        'foto_ruta' => 'fotos/soplado/AF-SOP-D.jpg',
        'ubicacion_destino' => 'SALA_DE_BAJA',
    ])->assertStatus(422)->assertJsonValidationErrors('ubicacion_destino');
});

it('rechaza el soplado sin evidencia fotográfica', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-F', 'usuario_cargue' => $this->analista->id]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-SOP-F',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $this->postJson('/api/soplado', [
        'placa_id' => 'AF-SOP-F',
        'energiza' => true,
        'da_video' => true,
        'acceso_bios' => true,
        'tipo_suciedad' => 'polvo',
        'aplica_pasta_termica' => true,
        'ubicacion_destino' => 'ALMACEN',
    ])->assertStatus(422)->assertJsonValidationErrors('foto_ruta');
});
