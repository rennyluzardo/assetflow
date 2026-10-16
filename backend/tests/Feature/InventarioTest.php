<?php

use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('inventario.ver');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create([
        'email' => 'inventario.admin@assetflow.test',
    ]);

    $this->admin->givePermissionTo('inventario.ver');

    Sanctum::actingAs($this->admin);
});

it('pagina el inventario por defecto a 250 registros por página', function () {
    InventarioGeneral::factory()->count(260)->create(['usuario_cargue' => $this->admin->id]);

    $respuesta = $this->getJson('/api/inventario');

    $respuesta->assertOk()
        ->assertJsonPath('meta.per_page', 250)
        ->assertJsonPath('meta.total', 260)
        ->assertJsonCount(250, 'data');
});

it('filtra la tabla maestra por número de traslado', function () {
    InventarioGeneral::factory()->count(2)->create([
        'num_traslado' => 'TR-FILTRO',
        'usuario_cargue' => $this->admin->id,
    ]);

    InventarioGeneral::factory()->count(3)->create([
        'num_traslado' => 'TR-OTRO',
        'usuario_cargue' => $this->admin->id,
    ]);

    $this->getJson('/api/inventario?num_traslado=TR-FILTRO')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

it('filtra por estado_actual, incluyendo los activos sin estado', function () {
    InventarioGeneral::factory()->count(3)->create([
        'estado_actual' => 'COMPLETADO',
        'usuario_cargue' => $this->admin->id,
    ]);

    InventarioGeneral::factory()->count(2)->create([
        'estado_actual' => null,
        'usuario_cargue' => $this->admin->id,
    ]);

    $this->getJson('/api/inventario?estado_actual=COMPLETADO')
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('data.0.estado_actual', 'COMPLETADO');

    $this->getJson('/api/inventario?estado_actual=SIN_ESTADO')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

it('busca por placa_id, serial o descripcion de forma insensible a mayúsculas', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-BUSQUEDA-1',
        'serial' => 'SN-UNICO-999',
        'descripcion' => 'CPU',
        'usuario_cargue' => $this->admin->id,
    ]);

    $this->getJson('/api/inventario?search=UNICO-999')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.serial', 'SN-UNICO-999');

    $this->getJson('/api/inventario?search=af-busqueda')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.placa_id', 'AF-BUSQUEDA-1');
});

it('bloquea con 403 a un usuario sin el permiso inventario.ver', function () {
    $sinPermiso = User::factory()->create();
    Sanctum::actingAs($sinPermiso);

    $this->getJson('/api/inventario')->assertStatus(403);
});

it('rechaza un per_page superior al máximo de 1000', function () {
    $this->getJson('/api/inventario?per_page=5000')
        ->assertStatus(422)
        ->assertJsonValidationErrors('per_page');
});
