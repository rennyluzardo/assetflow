<?php

use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['cargue_masivo.ejecutar', 'inventario.ver'] as $permiso) {
        Permission::findOrCreate($permiso);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create([
        'name' => 'Operario de Cargue',
        'email' => 'operario.cargue@assetflow.test',
    ]);

    $this->admin->givePermissionTo(['cargue_masivo.ejecutar', 'inventario.ver']);

    Sanctum::actingAs($this->admin);
});

/** Construye un archivo CSV de prueba a partir de su contenido crudo. */
function csvCargue(string $contenido): UploadedFile
{
    return UploadedFile::fake()->createWithContent('cargue.csv', $contenido);
}

it('procesa un CSV y persiste en inventario_general con intervenido=0 y usuario del token', function () {
    $contenido = implode("\n", [
        'Identificador 1,Identificador 2,Marca,Ref. Principal,Descripcion',
        'AF-CAR-1,SN-CAR-1,Dell,OptiPlex 7050,CPU',
        'AF-CAR-2,SN-CAR-2,HP,ProDesk 600,CPU',
    ]);

    $respuesta = $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-CAR',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json']);

    $respuesta->assertCreated();
    expect($respuesta->json('data.insertados'))->toBe(2);
    expect($respuesta->json('data.omitidos'))->toBe(0);

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-CAR-1',
        'serial' => 'SN-CAR-1',
        'marca' => 'Dell',
        'referencia' => 'OptiPlex 7050',
        'num_traslado' => 'TR-CAR',
        'intervenido' => false,
        'estado_actual' => null,
        'usuario_cargue' => $this->admin->id,
    ]);

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-CAR-2',
        'usuario_cargue' => $this->admin->id,
    ]);
});

it('soporta CSV con separador de punto y coma (exporte español de Cubit)', function () {
    $contenido = implode("\n", [
        'Identificador 1;Identificador 2;Descripcion',
        'AF-SEMI;SN-SEMI;CPU',
    ]);

    $respuesta = $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-SEMI',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json']);

    $respuesta->assertCreated();
    expect($respuesta->json('data.insertados'))->toBe(1);

    $this->assertDatabaseHas('inventario_general', ['placa_id' => 'AF-SEMI', 'serial' => 'SN-SEMI']);
});

it('rechaza con 422 cuando faltan columnas obligatorias', function () {
    $contenido = implode("\n", [
        'Identificador 1,Marca',
        'AF-SIN-COL,Dell',
    ]);

    $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-SIN',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('archivo');
});

it('rechaza con 422 un archivo con extensión no soportada', function () {
    $archivo = UploadedFile::fake()->createWithContent('cargue.txt', 'no importa');

    $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-TXT',
        'archivo' => $archivo,
    ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('archivo');
});

it('omite las filas con placa duplicada dentro del mismo archivo', function () {
    $contenido = implode("\n", [
        'Identificador 1,Identificador 2,Descripcion',
        'AF-DUP,SN-DUP-1,CPU',
        'AF-DUP,SN-DUP-2,CPU',
    ]);

    $respuesta = $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-DUP',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json']);

    $respuesta->assertCreated();
    expect($respuesta->json('data.insertados'))->toBe(1);
    expect($respuesta->json('data.omitidos'))->toBe(1);
});

it('omite las filas cuya placa ya existe en la base de datos', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-EXISTE',
        'usuario_cargue' => $this->admin->id,
    ]);

    $contenido = implode("\n", [
        'Identificador 1,Identificador 2,Descripcion',
        'AF-EXISTE,SN-EXISTE,CPU',
        'AF-NUEVA,SN-NUEVA,CPU',
    ]);

    $respuesta = $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-MIX',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json']);

    $respuesta->assertCreated();
    expect($respuesta->json('data.insertados'))->toBe(1);
    expect($respuesta->json('data.omitidos'))->toBe(1);

    $this->assertDatabaseHas('inventario_general', ['placa_id' => 'AF-NUEVA']);
});

it('bloquea con 403 a un usuario sin el permiso cargue_masivo.ejecutar', function () {
    $sinPermiso = User::factory()->create();
    Sanctum::actingAs($sinPermiso);

    $contenido = implode("\n", [
        'Identificador 1,Identificador 2,Descripcion',
        'AF-NO-PERM,SN-NO-PERM,CPU',
    ]);

    $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-NO-PERM',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json'])->assertStatus(403);
});
