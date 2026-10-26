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

it('ordena ascendentemente por una columna de la whitelist', function () {
    InventarioGeneral::factory()->create(['serial' => 'SN-Z', 'usuario_cargue' => $this->admin->id]);
    InventarioGeneral::factory()->create(['serial' => 'SN-A', 'usuario_cargue' => $this->admin->id]);

    $this->getJson('/api/inventario?sort=serial&direction=asc')
        ->assertOk()
        ->assertJsonPath('data.0.serial', 'SN-A')
        ->assertJsonPath('data.1.serial', 'SN-Z');
});

it('ordena descendentemente por una columna de la whitelist', function () {
    InventarioGeneral::factory()->create(['serial' => 'SN-Z', 'usuario_cargue' => $this->admin->id]);
    InventarioGeneral::factory()->create(['serial' => 'SN-A', 'usuario_cargue' => $this->admin->id]);

    $this->getJson('/api/inventario?sort=serial&direction=desc')
        ->assertOk()
        ->assertJsonPath('data.0.serial', 'SN-Z')
        ->assertJsonPath('data.1.serial', 'SN-A');
});

it('permite ordenar por marca y descripción, columnas visibles sin índice', function () {
    InventarioGeneral::factory()->create([
        'marca' => 'Zebra',
        'descripcion' => 'Portátil',
        'usuario_cargue' => $this->admin->id,
    ]);

    InventarioGeneral::factory()->create([
        'marca' => 'Acer',
        'descripcion' => 'CPU',
        'usuario_cargue' => $this->admin->id,
    ]);

    $this->getJson('/api/inventario?sort=marca&direction=asc')
        ->assertOk()
        ->assertJsonPath('data.0.marca', 'Acer');

    $this->getJson('/api/inventario?sort=descripcion&direction=asc')
        ->assertOk()
        ->assertJsonPath('data.0.descripcion', 'CPU');
});

it('aplica asc cuando se envía sort sin direction', function () {
    InventarioGeneral::factory()->create(['serial' => 'SN-Z', 'usuario_cargue' => $this->admin->id]);
    InventarioGeneral::factory()->create(['serial' => 'SN-A', 'usuario_cargue' => $this->admin->id]);

    $this->getJson('/api/inventario?sort=serial')
        ->assertOk()
        ->assertJsonPath('data.0.serial', 'SN-A');
});

it('rechaza una columna de orden fuera de la whitelist', function () {
    $this->getJson('/api/inventario?sort=password&direction=asc')
        ->assertStatus(422)
        ->assertJsonValidationErrors('sort');
});

it('rechaza una dirección de orden inválida', function () {
    $this->getJson('/api/inventario?sort=serial&direction=DROP')
        ->assertStatus(422)
        ->assertJsonValidationErrors('direction');
});

it('mantiene el orden por defecto id desc cuando no se envía sort', function () {
    $ids = InventarioGeneral::factory()
        ->count(3)
        ->create(['usuario_cargue' => $this->admin->id])
        ->pluck('id')
        ->all();

    rsort($ids);

    $this->getJson('/api/inventario')
        ->assertOk()
        ->assertJsonPath('data.0.id', $ids[0])
        ->assertJsonPath('data.2.id', $ids[2]);
});

it('ordena los estados según el flujo operativo, no alfabéticamente', function () {
    InventarioGeneral::factory()->create([
        'estado_actual' => 'BAJA_NO_SIRVE',
        'usuario_cargue' => $this->admin->id,
    ]);

    InventarioGeneral::factory()->create([
        'estado_actual' => 'COMPLETADO',
        'usuario_cargue' => $this->admin->id,
    ]);

    InventarioGeneral::factory()->create([
        'estado_actual' => 'POR_SOPLAR',
        'usuario_cargue' => $this->admin->id,
    ]);

    InventarioGeneral::factory()->create([
        'estado_actual' => null,
        'usuario_cargue' => $this->admin->id,
    ]);

    $this->getJson('/api/inventario?sort=estado_actual&direction=asc')
        ->assertOk()
        ->assertJsonPath('data.0.estado_actual', null)
        ->assertJsonPath('data.1.estado_actual', 'POR_SOPLAR')
        ->assertJsonPath('data.2.estado_actual', 'COMPLETADO')
        ->assertJsonPath('data.3.estado_actual', 'BAJA_NO_SIRVE');

    $this->getJson('/api/inventario?sort=estado_actual&direction=desc')
        ->assertOk()
        ->assertJsonPath('data.0.estado_actual', 'BAJA_NO_SIRVE')
        ->assertJsonPath('data.3.estado_actual', null);
});

it('ordena las ubicaciones según el flujo operativo', function () {
    foreach (['SALA_DE_BAJA', 'ALMACEN', 'DBAN', 'SALA_DE_GARANTIA'] as $ubicacion) {
        InventarioGeneral::factory()->create([
            'ubicacion_actual' => $ubicacion,
            'usuario_cargue' => $this->admin->id,
        ]);
    }

    $this->getJson('/api/inventario?sort=ubicacion_actual&direction=asc')
        ->assertOk()
        ->assertJsonPath('data.0.ubicacion_actual', 'SALA_DE_GARANTIA')
        ->assertJsonPath('data.1.ubicacion_actual', 'DBAN')
        ->assertJsonPath('data.2.ubicacion_actual', 'ALMACEN')
        ->assertJsonPath('data.3.ubicacion_actual', 'SALA_DE_BAJA');
});

it('pagina de forma estable al ordenar por una columna no única', function () {
    InventarioGeneral::factory()
        ->count(30)
        ->create(['estado_actual' => 'COMPLETADO', 'usuario_cargue' => $this->admin->id]);

    $pagina1 = $this->getJson('/api/inventario?sort=estado_actual&direction=asc&per_page=10&page=1')
        ->assertOk()
        ->json('data');

    $pagina2 = $this->getJson('/api/inventario?sort=estado_actual&direction=asc&per_page=10&page=2')
        ->assertOk()
        ->json('data');

    expect(array_intersect(array_column($pagina1, 'id'), array_column($pagina2, 'id')))->toBeEmpty();
});

it('combina el orden con los filtros existentes', function () {
    InventarioGeneral::factory()->create([
        'num_traslado' => 'TR-ORDEN',
        'serial' => 'SN-Z',
        'usuario_cargue' => $this->admin->id,
    ]);

    InventarioGeneral::factory()->create([
        'num_traslado' => 'TR-ORDEN',
        'serial' => 'SN-A',
        'usuario_cargue' => $this->admin->id,
    ]);

    InventarioGeneral::factory()->create([
        'num_traslado' => 'TR-OTRO',
        'serial' => 'SN-M',
        'usuario_cargue' => $this->admin->id,
    ]);

    $this->getJson('/api/inventario?num_traslado=TR-ORDEN&sort=serial&direction=asc')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.serial', 'SN-A')
        ->assertJsonPath('data.1.serial', 'SN-Z');
});
