<?php

use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

/** Construye un archivo XLSX real a partir de una lista de filas (base-0 por índice). */
function xlsxCargue(array $filas): UploadedFile
{
    $spreadsheet = new Spreadsheet();
    $hoja = $spreadsheet->getActiveSheet();

    foreach ($filas as $numeroFila => $fila) {
        foreach (array_values($fila) as $columna => $valor) {
            $hoja->setCellValue([$columna + 1, $numeroFila + 1], $valor);
        }
    }

    $ruta = sys_get_temp_dir().'/cargue_'.uniqid('', true).'.xlsx';
    (new Xlsx($spreadsheet))->save($ruta);

    return new UploadedFile($ruta, 'cargue.xlsx', null, null, true);
}

it('procesa un CSV y persiste en inventario_general con intervenido=0 y usuario del token', function () {
    $contenido = implode("\n", [
        'Identificador 1,Identificador 2,Marca,Ref. Principal,Descripcion',
        'AF-CAR-1,SN-CAR-1,Dell,OptiPlex 7050,CPU',
        'AF-CAR-2,SN-CAR-2,HP,ProDesk 600,CPU',
    ]);

    $respuesta = $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-CAR',
        'ubicacion_origen' => 'Bogotá',
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

it('crea la cabecera del traslado en `traslados` dentro del mismo cargue', function () {
    $contenido = implode("\n", [
        'Identificador 1,Identificador 2,Descripcion',
        'AF-CAB-1,SN-CAB-1,CPU',
        'AF-CAB-2,SN-CAB-2,CPU',
    ]);

    $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-CAB',
        'ubicacion_origen' => 'Medellín',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json'])->assertCreated();

    $this->assertDatabaseHas('traslados', [
        'codigo' => 'TR-CAB',
        'zona_origen' => 'MEDELLIN',
        'ubicacion_origen_exacta' => 'Medellín',
        'zona_destino' => 'BOGOTA',
        'estado' => 'EN_TRANSITO',
        'analista_id' => $this->admin->id,
    ]);
});

it('no duplica la cabecera del traslado si el mismo lote se reingresa', function () {
    $contenido = implode("\n", [
        'Identificador 1,Identificador 2,Descripcion',
        'AF-CAB-RE-1,SN-CAB-RE-1,CPU',
    ]);

    $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-CAB-RE',
        'ubicacion_origen' => 'Bogotá',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json'])->assertCreated();

    // Segundo cargue con el mismo número y otra placa: la cabecera se conserva.
    $segundo = implode("\n", [
        'Identificador 1,Identificador 2,Descripcion',
        'AF-CAB-RE-2,SN-CAB-RE-2,CPU',
    ]);

    $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-CAB-RE',
        'ubicacion_origen' => 'Bogotá',
        'archivo' => csvCargue($segundo),
    ], ['Accept' => 'application/json'])->assertCreated();

    $this->assertDatabaseCount('traslados', 1);
    $this->assertDatabaseHas('traslados', ['codigo' => 'TR-CAB-RE', 'zona_origen' => 'BOGOTA']);
});

it('estampa la ubicación de origen manual y la fecha de cargue en todo el lote', function () {
    $contenido = implode("\n", [
        'Identificador 1,Identificador 2,Descripcion',
        'AF-ORIGEN-1,SN-ORIGEN-1,CPU',
        'AF-ORIGEN-2,SN-ORIGEN-2,CPU',
    ]);

    $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-ORIGEN',
        'ubicacion_origen' => 'Cali',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json'])->assertCreated();

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-ORIGEN-1',
        'num_traslado' => 'TR-ORIGEN',
        'ubicacion_origen' => 'Cali',
    ]);

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-ORIGEN-2',
        'num_traslado' => 'TR-ORIGEN',
        'ubicacion_origen' => 'Cali',
    ]);

    expect(InventarioGeneral::where('placa_id', 'AF-ORIGEN-1')->value('fecha_cargue'))
        ->not->toBeNull();
});

it('rechaza con 422 cuando falta la ubicación de origen', function () {
    $contenido = implode("\n", [
        'Identificador 1,Identificador 2,Descripcion',
        'AF-SIN-ORIGEN,SN-SIN-ORIGEN,CPU',
    ]);

    $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-SIN-ORIGEN',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('ubicacion_origen');
});

it('salta las 3 filas de títulos institucionales del XLSX de Cubit', function () {
    $filas = [
        ['Teleperformance - Reporte de Activos'],
        ['Generado: 2026-11-10'],
        [''],
        ['Identificador 1', 'Identificador 2', 'Marca', 'Ref. Principal', 'Descripcion'],
        ['AF-XLSX-1', 'SN-XLSX-1', 'Dell', 'OptiPlex 7050', 'CPU'],
        ['AF-XLSX-2', 'SN-XLSX-2', 'HP', 'ProDesk 600', 'CPU'],
    ];

    $respuesta = $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-XLSX',
        'ubicacion_origen' => 'Bogotá',
        'archivo' => xlsxCargue($filas),
    ], ['Accept' => 'application/json']);

    $respuesta->assertCreated();
    expect($respuesta->json('data.insertados'))->toBe(2);
    expect($respuesta->json('data.omitidos'))->toBe(0);

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-XLSX-1',
        'serial' => 'SN-XLSX-1',
        'marca' => 'Dell',
        'num_traslado' => 'TR-XLSX',
    ]);
});

it('soporta CSV con separador de punto y coma (exporte español de Cubit)', function () {
    $contenido = implode("\n", [
        'Identificador 1;Identificador 2;Descripcion',
        'AF-SEMI;SN-SEMI;CPU',
    ]);

    $respuesta = $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-SEMI',
        'ubicacion_origen' => 'Medellín',
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
        'ubicacion_origen' => 'Bogotá',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('archivo');
});

it('rechaza con 422 un archivo con extensión no soportada', function () {
    $archivo = UploadedFile::fake()->createWithContent('cargue.txt', 'no importa');

    $this->post('/api/cargue-masivo', [
        'num_traslado' => 'TR-TXT',
        'ubicacion_origen' => 'Bogotá',
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
        'ubicacion_origen' => 'Medellín',
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
        'ubicacion_origen' => 'Bogotá',
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
        'ubicacion_origen' => 'Bogotá',
        'archivo' => csvCargue($contenido),
    ], ['Accept' => 'application/json'])->assertStatus(403);
});
