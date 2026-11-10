<?php

use App\Models\InventarioGeneral;
use App\Models\Traslado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['traslados.ver', 'traslados.registrar'] as $permiso) {
        Permission::findOrCreate($permiso);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->usuario = User::factory()->create([
        'name' => 'Operario Traslados',
        'email' => 'traslados@assetflow.test',
    ]);

    $this->usuario->givePermissionTo(['traslados.ver', 'traslados.registrar']);

    Sanctum::actingAs($this->usuario);
});

it('registra un traslado con el analista del token y estado EN_TRANSITO', function () {
    $respuesta = $this->postJson('/api/traslados', [
        'codigo' => 'TR-1001',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
    ]);

    $respuesta->assertCreated();
    expect($respuesta->json('data.estado'))->toBe('EN_TRANSITO');
    expect($respuesta->json('data.codigo'))->toBe('TR-1001');
    expect($respuesta->json('data.analista_id'))->toBe($this->usuario->id);

    $this->assertDatabaseHas('traslados', [
        'codigo' => 'TR-1001',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
        'estado' => 'EN_TRANSITO',
        'analista_id' => $this->usuario->id,
    ]);
});

it('rechaza un código de traslado duplicado', function () {
    Traslado::create([
        'codigo' => 'TR-DUP',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
        'estado' => 'EN_TRANSITO',
        'analista_id' => $this->usuario->id,
    ]);

    $this->postJson('/api/traslados', [
        'codigo' => 'TR-DUP',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
    ])->assertStatus(422)->assertJsonValidationErrors('codigo');
});

it('lista los traslados paginados con la estructura {data, meta}', function () {
    Traslado::create([
        'codigo' => 'TR-L1',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
        'estado' => 'EN_TRANSITO',
        'analista_id' => $this->usuario->id,
    ]);

    Traslado::create([
        'codigo' => 'TR-L2',
        'zona_origen' => 'MEDELLIN',
        'zona_destino' => 'BOGOTA',
        'estado' => 'RECIBIDO',
        'analista_id' => $this->usuario->id,
    ]);

    $respuesta = $this->getJson('/api/traslados')->assertOk();

    expect($respuesta->json('meta.total'))->toBe(2);
    expect(count($respuesta->json('data')))->toBe(2);

    // Filtro por estado.
    $enTransito = $this->getJson('/api/traslados?estado=EN_TRANSITO')->assertOk();
    expect($enTransito->json('meta.total'))->toBe(1);
    expect($enTransito->json('data.0.codigo'))->toBe('TR-L1');
});

it('recibe un traslado en la sede correcta y devuelve sus equipos', function () {
    $this->usuario->update(['sede' => 'MEDELLIN']);

    Traslado::create([
        'codigo' => 'TR-REC',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
        'estado' => 'EN_TRANSITO',
        'analista_id' => $this->usuario->id,
    ]);

    InventarioGeneral::factory()->create([
        'num_traslado' => 'TR-REC',
        'usuario_cargue' => $this->usuario->id,
    ]);

    $respuesta = $this->postJson('/api/traslados/TR-REC/recibir');

    $respuesta->assertOk();
    expect($respuesta->json('data.traslado.estado'))->toBe('RECIBIDO');
    expect(count($respuesta->json('data.equipos')))->toBe(1);
    expect($respuesta->json('data.equipos.0.num_traslado'))->toBe('TR-REC');
});

it('permite la recepción sin restricción cuando el usuario no tiene sede asignada', function () {
    Traslado::create([
        'codigo' => 'TR-ADMIN',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
        'estado' => 'EN_TRANSITO',
        'analista_id' => $this->usuario->id,
    ]);

    $respuesta = $this->postJson('/api/traslados/TR-ADMIN/recibir')->assertOk();

    expect($respuesta->json('data.traslado.estado'))->toBe('RECIBIDO');
});

it('bloquea la recepción cuando la sede física del operario no coincide con el destino', function () {
    $this->usuario->update(['sede' => 'BOGOTA']);

    Traslado::create([
        'codigo' => 'TR-SEDE',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
        'estado' => 'EN_TRANSITO',
        'analista_id' => $this->usuario->id,
    ]);

    $this->postJson('/api/traslados/TR-SEDE/recibir')
        ->assertStatus(422)
        ->assertJsonValidationErrors('codigo');

    $this->assertDatabaseHas('traslados', [
        'codigo' => 'TR-SEDE',
        'estado' => 'EN_TRANSITO',
    ]);
});

it('persiste las ubicaciones exactas de origen y destino', function () {
    $this->postJson('/api/traslados', [
        'codigo' => 'TR-EXACTAS',
        'zona_origen' => 'BOGOTA',
        'ubicacion_origen_exacta' => 'Bodega Norte',
        'zona_destino' => 'MEDELLIN',
        'ubicacion_destino_exacta' => 'Sala 2',
    ])->assertCreated();

    $this->assertDatabaseHas('traslados', [
        'codigo' => 'TR-EXACTAS',
        'ubicacion_origen_exacta' => 'Bodega Norte',
        'ubicacion_destino_exacta' => 'Sala 2',
    ]);
});

it('bloquea el registro cuando la sede física no coincide con el origen', function () {
    $this->usuario->update(['sede' => 'MEDELLIN']);

    $this->postJson('/api/traslados', [
        'codigo' => 'TR-ORIGEN',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
    ])->assertStatus(422)->assertJsonValidationErrors('zona_origen');

    $this->assertDatabaseMissing('traslados', ['codigo' => 'TR-ORIGEN']);
});

it('permite el registro cuando la sede coincide con el origen', function () {
    $this->usuario->update(['sede' => 'BOGOTA']);

    $this->postJson('/api/traslados', [
        'codigo' => 'TR-ORIGEN-OK',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
    ])->assertCreated();

    $this->assertDatabaseHas('traslados', ['codigo' => 'TR-ORIGEN-OK']);
});

it('lista los equipos asociados a un traslado sin mutar su estado', function () {
    Traslado::create([
        'codigo' => 'TR-EQ',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
        'estado' => 'EN_TRANSITO',
        'analista_id' => $this->usuario->id,
    ]);

    InventarioGeneral::factory()->create([
        'num_traslado' => 'TR-EQ',
        'usuario_cargue' => $this->usuario->id,
        'ubicacion_origen' => 'BOGOTA',
    ]);

    $respuesta = $this->getJson('/api/traslados/TR-EQ/equipos')->assertOk();

    expect($respuesta->json('data.traslado.estado'))->toBe('EN_TRANSITO');
    expect(count($respuesta->json('data.equipos')))->toBe(1);
    expect($respuesta->json('data.equipos.0.ubicacion_origen'))->toBe('BOGOTA');
});

it('devuelve el activo al escanear la placa en traslados', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-TRASLADO',
        'num_traslado' => 'TR-SCAN',
        'ubicacion_origen' => 'MEDELLIN',
        'usuario_cargue' => $this->usuario->id,
    ]);

    $respuesta = $this->getJson('/api/traslados/buscar/AF-TRASLADO')->assertOk();

    expect($respuesta->json('data.num_traslado'))->toBe('TR-SCAN');
    expect($respuesta->json('data.ubicacion_origen'))->toBe('MEDELLIN');
});

it('incluye el conteo de equipos asociados en el listado', function () {
    Traslado::create([
        'codigo' => 'TR-COUNT',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'MEDELLIN',
        'estado' => 'EN_TRANSITO',
        'analista_id' => $this->usuario->id,
    ]);

    InventarioGeneral::factory()->create([
        'num_traslado' => 'TR-COUNT',
        'usuario_cargue' => $this->usuario->id,
    ]);
    InventarioGeneral::factory()->create([
        'num_traslado' => 'TR-COUNT',
        'usuario_cargue' => $this->usuario->id,
    ]);

    $respuesta = $this->getJson('/api/traslados')->assertOk();

    expect($respuesta->json('data.0.codigo'))->toBe('TR-COUNT');
    expect($respuesta->json('data.0.equipos_count'))->toBe(2);
});

it('sincroniza creando cabeceras para num_traslado sin traslado', function () {
    InventarioGeneral::factory()->create([
        'num_traslado' => 'QA-CPU',
        'usuario_cargue' => $this->usuario->id,
    ]);
    InventarioGeneral::factory()->create([
        'num_traslado' => 'QA-LAP',
        'ubicacion_origen' => 'Medellín',
        'usuario_cargue' => $this->usuario->id,
    ]);

    $this->artisan('traslados:sincronizar')->assertSuccessful();

    $this->assertDatabaseHas('traslados', [
        'codigo' => 'QA-CPU',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'BOGOTA',
        'estado' => 'EN_TRANSITO',
    ]);

    $this->assertDatabaseHas('traslados', [
        'codigo' => 'QA-LAP',
        'zona_origen' => 'MEDELLIN',
        'ubicacion_origen_exacta' => 'Medellín',
    ]);
});

it('no sobrescribe traslados existentes al sincronizar', function () {
    Traslado::create([
        'codigo' => 'TR-EXISTE',
        'zona_origen' => 'MEDELLIN',
        'zona_destino' => 'BOGOTA',
        'estado' => 'RECIBIDO',
        'analista_id' => $this->usuario->id,
    ]);

    InventarioGeneral::factory()->create([
        'num_traslado' => 'TR-EXISTE',
        'usuario_cargue' => $this->usuario->id,
    ]);

    $this->artisan('traslados:sincronizar')->assertSuccessful();

    $this->assertDatabaseHas('traslados', [
        'codigo' => 'TR-EXISTE',
        'zona_origen' => 'MEDELLIN',
        'estado' => 'RECIBIDO',
    ]);

    $this->assertDatabaseCount('traslados', 1);
});
