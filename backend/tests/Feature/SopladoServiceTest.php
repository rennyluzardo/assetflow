<?php

use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    foreach (['cpus.registrar', 'soplado.registrar', 'soplado.ver_bitacora'] as $permiso) {
        Permission::findOrCreate($permiso);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->analista = User::factory()->create([
        'name' => 'Operario de Soplado',
        'email' => 'operario.soplado@assetflow.test',
    ]);

    $this->analista->givePermissionTo(['cpus.registrar', 'soplado.registrar', 'soplado.ver_bitacora']);

    Sanctum::actingAs($this->analista);
});

function sopladoPayload(string $placa, string $destino = 'ALMACEN'): array
{
    return [
        'placa_id' => $placa,
        'energiza' => true,
        'da_video' => true,
        'acceso_bios' => true,
        'tipo_suciedad' => 'polvo',
        'aplica_pasta_termica' => true,
        'foto_ruta' => UploadedFile::fake()->image('evidencia.jpg'),
        'ubicacion_destino' => $destino,
    ];
}

it('cierra un equipo Por soplar de origen Novedad a Completado', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-N', 'usuario_cargue' => $this->analista->id]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-SOP-N',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $respuesta = $this->post('/api/soplado', sopladoPayload('AF-SOP-N'), ['Accept' => 'application/json']);

    $respuesta->assertCreated();
    expect($respuesta->json('data.foto_ruta'))->toStartWith('soplado/');

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

    $this->post('/api/soplado', sopladoPayload('AF-SOP-I', 'DBAN'), ['Accept' => 'application/json'])->assertCreated();

    $this->assertDatabaseHas('equipos', ['placa_id' => 'AF-SOP-I', 'estado' => 'INTERVENIDO']);
    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-SOP-I',
        'intervenido' => true,
        'ubicacion_actual' => 'DBAN',
    ]);
});

it('rechaza el soplado de un equipo que no está Por soplar', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-X', 'usuario_cargue' => $this->analista->id]);

    $this->post('/api/soplado', sopladoPayload('AF-SOP-X'), ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('placa_id');
});

it('rechaza el soplado con un destino no permitido', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-D', 'usuario_cargue' => $this->analista->id]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-SOP-D',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $this->post('/api/soplado', sopladoPayload('AF-SOP-D', 'SALA_DE_BAJA'), ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('ubicacion_destino');
});

it('rechaza el soplado sin evidencia fotográfica', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-F', 'usuario_cargue' => $this->analista->id]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-SOP-F',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $payload = sopladoPayload('AF-SOP-F');
    unset($payload['foto_ruta']);

    $this->post('/api/soplado', $payload, ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('foto_ruta');
});

it('devuelve el equipo activo Por soplar desde /soplado/buscar', function () {
    InventarioGeneral::factory()->create(['placa_id' => 'AF-SOP-B', 'usuario_cargue' => $this->analista->id]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-SOP-B',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $this->getJson('/api/soplado/buscar/AF-SOP-B')
        ->assertOk()
        ->assertJsonPath('data.placa_id', 'AF-SOP-B')
        ->assertJsonPath('data.equipo_activo.estado', 'POR_SOPLAR');
});
