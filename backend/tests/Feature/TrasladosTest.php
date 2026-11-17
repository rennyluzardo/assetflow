<?php

use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['traslados.ver', 'traslados.registrar', 'inventario.ver'] as $permiso) {
        Permission::findOrCreate($permiso);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->creador = User::factory()->create(['name' => 'Creador Traslados']);
    $this->receptor = User::factory()->create(['name' => 'Receptor Traslados']);

    $this->creador->givePermissionTo(['traslados.ver', 'traslados.registrar']);
    $this->receptor->givePermissionTo(['traslados.ver', 'traslados.registrar']);

    $this->inventario = function (string $placa, string $zona = 'Almacén', string $ubicacion = 'ALMACEN'): InventarioGeneral {
        return InventarioGeneral::factory()->create([
            'placa_id' => $placa,
            'zona_actual' => $zona,
            'ubicacion_actual' => $ubicacion,
        ]);
    };

    $this->crearTraslado = function (string $zonaDestino = 'BOGOTA', string $ubicacionDestino = 'SALA_DE_GARANTIA'): array {
        return $this->postJson('/api/traslados', [
            'zona_destino' => $zonaDestino,
            'ubicacion_destino' => $ubicacionDestino,
            'observaciones' => 'Lote de prueba',
        ])->assertCreated()->json('data');
    };

    $this->xlsxCubit = function (array $filas): UploadedFile {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($filas, null, 'A1');

        $ruta = tempnam(sys_get_temp_dir(), 'cubit');
        (new Xlsx($spreadsheet))->save($ruta);
        $contenido = file_get_contents($ruta);
        @unlink($ruta);

        return UploadedFile::fake()->createWithContent('repItemTrasladoElementos.xlsx', $contenido);
    };

    Sanctum::actingAs($this->creador);
});

it('registra un traslado ABIERTO con código autogenerado, origen nulo y creador del token', function () {
    $traslado = ($this->crearTraslado)();

    expect($traslado['estado'])->toBe('ABIERTO');
    expect($traslado['codigo'])->toBe('328351');
    expect($traslado['zona_origen'])->toBeNull();
    expect($traslado['ubicacion_origen'])->toBeNull();
    expect($traslado['zona_destino'])->toBe('BOGOTA');
    expect($traslado['creado_por_id'])->toBe($this->creador->id);
});

it('genera códigos consecutivos sin reutilizar los históricos', function () {
    $primero = ($this->crearTraslado)();
    $segundo = ($this->crearTraslado)();

    expect($primero['codigo'])->toBe('328351');
    expect($segundo['codigo'])->toBe('328352');
});

it('bautiza el origen con la ubicación actual del primer equipo y lo fija para el lote', function () {
    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-ORIGEN-1');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-ORIGEN-1'])
        ->assertCreated();

    $this->assertDatabaseHas('traslados', [
        'id' => $traslado['id'],
        'zona_origen' => 'Almacén',
        'ubicacion_origen' => 'ALMACEN',
    ]);
});

it('rechaza agregar un equipo cuyo origen no coincide con el del lote', function () {
    $traslado = ($this->crearTraslado)();

    ($this->inventario)('AF-HOMO-1', 'Almacén', 'ALMACEN');
    ($this->inventario)('AF-HOMO-2', 'Almacén', 'SALA_DE_BAJA');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-HOMO-1'])
        ->assertCreated();

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-HOMO-2'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('placa_id');
});

it('rechaza agregar un equipo que ya se encuentra en la ubicación de destino', function () {
    $traslado = ($this->crearTraslado)('Almacén', 'ALMACEN');

    ($this->inventario)('AF-IGUAL', 'Almacén', 'ALMACEN');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-IGUAL'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('equipo')
        ->assertJsonPath('errors.equipo.0', 'Operación denegada (TC-05): El equipo con placa AF-IGUAL ya se encuentra físicamente en el destino seleccionado (ALMACEN).');

    $this->assertDatabaseMissing('traslado_equipos', [
        'traslado_id' => $traslado['id'],
        'placa_id' => 'AF-IGUAL',
    ]);

    // El lote no se bautiza con un origen inválido.
    $this->assertDatabaseHas('traslados', [
        'id' => $traslado['id'],
        'zona_origen' => null,
        'ubicacion_origen' => null,
    ]);
});

it('rechaza el equipo por su ubicación aunque la zona de origen difiera de la del destino', function () {
    $traslado = ($this->crearTraslado)('BOGOTA', 'SALA_DE_GARANTIA');

    ($this->inventario)('AF-DESTINO-ZONA', 'MEDELLIN', 'SALA_DE_GARANTIA');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-DESTINO-ZONA'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('equipo')
        ->assertJsonPath('errors.equipo.0', 'Operación denegada (TC-05): El equipo con placa AF-DESTINO-ZONA ya se encuentra físicamente en el destino seleccionado (SALA DE GARANTIA).');
});

it('rechaza un equipo que ya está en el destino aunque el lote tenga origen bautizado', function () {
    $traslado = ($this->crearTraslado)('BOGOTA', 'SALA_DE_GARANTIA');

    ($this->inventario)('AF-ORIGEN-OK', 'Almacén', 'ALMACEN');
    ($this->inventario)('AF-YA-EN-DESTINO', 'Bogotá', 'SALA_DE_GARANTIA');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-ORIGEN-OK'])->assertCreated();

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-YA-EN-DESTINO'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('equipo')
        ->assertJsonPath('errors.equipo.0', 'Operación denegada (TC-05): El equipo con placa AF-YA-EN-DESTINO ya se encuentra físicamente en el destino seleccionado (SALA DE GARANTIA).');

    $this->assertDatabaseMissing('traslado_equipos', [
        'traslado_id' => $traslado['id'],
        'placa_id' => 'AF-YA-EN-DESTINO',
    ]);
});

it('rechaza el destino ignorando espacios y capitalización (TC-05)', function (string $ubicacionActual, string $ubicacionDestino, string $destinoCanonico) {
    $traslado = ($this->crearTraslado)('BOGOTA', $ubicacionDestino);
    ($this->inventario)('AF-TC05', 'Almacén', $ubicacionActual);

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-TC05'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('equipo')
        ->assertJsonPath('errors.equipo.0', "Operación denegada (TC-05): El equipo con placa AF-TC05 ya se encuentra físicamente en el destino seleccionado ({$destinoCanonico}).");

    $this->assertDatabaseMissing('traslado_equipos', [
        'traslado_id' => $traslado['id'],
        'placa_id' => 'AF-TC05',
    ]);
})->with([
    ['  almacen ', 'ALMACEN', 'ALMACEN'],
    ['Sala_De_Garantia', 'SALA_DE_GARANTIA', 'SALA DE GARANTIA'],
    ['sala de garantía   ', '  Sala de Garantía  ', 'SALA DE GARANTIA'],
]);

it('regresión TC-05: no permite re-trasladar al mismo destino equipos ya recibidos', function () {
    $placas = ['B257024', 'B148234', 'B270649', 'BT13699', 'B241334'];

    // Traslado 1 hacia "Sala de Garantía" (nombre legible con espacios y tilde).
    $traslado1 = ($this->crearTraslado)('BOGOTA', 'Sala de Garantía');

    foreach ($placas as $placa) {
        ($this->inventario)($placa, 'Almacén', 'ALMACEN');
        $this->postJson("/api/traslados/{$traslado1['id']}/equipos", ['placa_id' => $placa])->assertCreated();
    }

    $this->postJson("/api/traslados/{$traslado1['id']}/despachar")->assertOk();

    Sanctum::actingAs($this->receptor);
    foreach ($placas as $placa) {
        $this->postJson("/api/traslados/{$traslado1['id']}/equipos/verificar", ['placa_id' => $placa])->assertOk();
    }
    $this->postJson("/api/traslados/{$traslado1['id']}/recibir")->assertOk();

    // La recepción dejó los activos físicamente en el destino (nombre legible).
    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'B257024',
        'zona_actual' => 'BOGOTA',
        'ubicacion_actual' => 'Sala de Garantía',
    ]);

    // Traslado 2 hacia el mismo destino, ahora escrito como slug.
    Sanctum::actingAs($this->creador);
    $traslado2 = ($this->crearTraslado)('BOGOTA', 'SALA_DE_GARANTIA');

    // 1) Escáner individual: los 5 deben ser rechazados con 422.
    foreach ($placas as $placa) {
        $this->postJson("/api/traslados/{$traslado2['id']}/equipos", ['placa_id' => $placa])
            ->assertStatus(422)
            ->assertJsonValidationErrors('equipo')
            ->assertJsonPath('errors.equipo.0', "Operación denegada (TC-05): El equipo con placa {$placa} ya se encuentra físicamente en el destino seleccionado (SALA DE GARANTIA).");
    }

    // Solo persisten las 5 filas del traslado 1.
    $this->assertDatabaseCount('traslado_equipos', 5);

    // 2) Reimportación del Excel Cubit: los 5 deben ser rechazados sin
    //    sobrescribir su ubicación real (que ya es la del destino).
    $archivo = ($this->xlsxCubit)(array_merge(
        [
            ['Reporte de Traslado de Elementos'],
            [],
            [],
            ['Identificador 1', 'Identificador 2', 'Ref. Principal', 'Descripción', 'Zona Actual', 'Ubicación Actual'],
        ],
        array_map(
            fn (string $placa) => [$placa, "SN-{$placa}", 'PORTATIL LENOVO L14', 'PORTATIL', 'BOGOTA', '30052001965-ALMACEN'],
            $placas,
        ),
    ));

    $this->postJson("/api/traslados/{$traslado2['id']}/equipos/importar", ['archivo' => $archivo])
        ->assertOk()
        ->assertJsonPath('data.agregados', 0)
        ->assertJsonCount(5, 'data.errores');

    // La reimportación NO movió los activos: siguen donde los dejó la recepción.
    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'B257024',
        'ubicacion_actual' => 'Sala de Garantía',
    ]);

    $this->assertDatabaseCount('traslado_equipos', 5);
});

it('rechaza un equipo que ya viaja en otro traslado activo', function () {
    $primero = ($this->crearTraslado)();
    $segundo = ($this->crearTraslado)();

    ($this->inventario)('AF-ACTIVO');

    $this->postJson("/api/traslados/{$primero['id']}/equipos", ['placa_id' => 'AF-ACTIVO'])
        ->assertCreated();

    $this->postJson("/api/traslados/{$segundo['id']}/equipos", ['placa_id' => 'AF-ACTIVO'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('placa_id');
});

it('no permite agregar equipos a un traslado que no está ABIERTO', function () {
    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-CERRADO');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-CERRADO'])->assertCreated();
    $this->postJson("/api/traslados/{$traslado['id']}/despachar")->assertOk();

    ($this->inventario)('AF-CERRADO-2');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-CERRADO-2'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('traslado');
});

it('quita un equipo y reinicia el origen si el lote queda vacío', function () {
    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-QUITAR');

    $pivote = $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-QUITAR'])
        ->assertCreated()
        ->json('data');

    $this->deleteJson("/api/traslados/{$traslado['id']}/equipos/{$pivote['id']}")->assertOk();

    $this->assertDatabaseMissing('traslado_equipos', ['id' => $pivote['id']]);
    $this->assertDatabaseHas('traslados', [
        'id' => $traslado['id'],
        'zona_origen' => null,
        'ubicacion_origen' => null,
    ]);
});

it('despacha solo traslados ABIERTO con al menos un equipo', function () {
    $sinEquipos = ($this->crearTraslado)();
    $this->postJson("/api/traslados/{$sinEquipos['id']}/despachar")
        ->assertStatus(422)
        ->assertJsonValidationErrors('traslado');

    $conEquipo = ($this->crearTraslado)();
    ($this->inventario)('AF-DESPACHAR');

    $this->postJson("/api/traslados/{$conEquipo['id']}/equipos", ['placa_id' => 'AF-DESPACHAR'])->assertCreated();
    $this->postJson("/api/traslados/{$conEquipo['id']}/despachar")
        ->assertOk()
        ->assertJsonPath('data.estado', 'EN_TRANSITO');

    $this->assertDatabaseHas('traslados', [
        'id' => $conEquipo['id'],
        'estado' => 'EN_TRANSITO',
        'despachado_por_id' => $this->creador->id,
    ]);
});

it('marca un equipo como recibido dentro de un traslado en tránsito', function () {
    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-VERIFICAR');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-VERIFICAR'])->assertCreated();
    $this->postJson("/api/traslados/{$traslado['id']}/despachar")->assertOk();

    $this->postJson("/api/traslados/{$traslado['id']}/equipos/verificar", ['placa_id' => 'AF-VERIFICAR'])
        ->assertOk()
        ->assertJsonPath('data.recibido', true);

    $this->assertDatabaseHas('traslado_equipos', [
        'traslado_id' => $traslado['id'],
        'placa_id' => 'AF-VERIFICAR',
        'recibido' => true,
    ]);
});

it('bloquea la recepción a quien creó o despachó el traslado', function () {
    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-403');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-403'])->assertCreated();
    $this->postJson("/api/traslados/{$traslado['id']}/despachar")->assertOk();
    $this->postJson("/api/traslados/{$traslado['id']}/equipos/verificar", ['placa_id' => 'AF-403'])->assertOk();

    // El creador/despachador no puede recibir su propio traslado.
    $this->postJson("/api/traslados/{$traslado['id']}/recibir")->assertStatus(403);

    $this->assertDatabaseHas('traslados', ['id' => $traslado['id'], 'estado' => 'EN_TRANSITO']);
});

it('importa el reporte Cubit, normaliza la ubicación y bautiza el origen', function () {
    $this->creador->givePermissionTo('inventario.ver');

    $traslado = ($this->crearTraslado)('BOGOTA', 'ALMACEN');

    $archivo = ($this->xlsxCubit)([
        ['Reporte de Traslado de Elementos'],
        [],
        [],
        ['Identificador 1', 'Identificador 2', 'Ref. Principal', 'Descripción', 'Zona Actual', 'Ubicación Actual'],
        ['B257024', 'PW07G16X', 'PORTATIL LENOVO L14 I7 16GB', 'PORTATIL', 'BOGOTA', '30052001965-SALA_DE_GARANTIA'],
        ['B257025', 'PW07G17X', 'PORTATIL LENOVO L14 I7 16GB', 'PORTATIL', 'BOGOTA', '30052001965-SALA_DE_GARANTIA'],
    ]);

    $this->postJson("/api/traslados/{$traslado['id']}/equipos/importar", ['archivo' => $archivo])
        ->assertOk()
        ->assertJsonPath('data.agregados', 2)
        ->assertJsonPath('data.errores', []);

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'B257024',
        'serial' => 'PW07G16X',
        'referencia' => 'PORTATIL LENOVO L14 I7 16GB',
        'descripcion' => 'PORTATIL',
        'zona_actual' => 'BOGOTA',
        'ubicacion_actual' => 'SALA_DE_GARANTIA',
        'num_traslado' => null,
    ]);

    $this->assertDatabaseHas('traslados', [
        'id' => $traslado['id'],
        'zona_origen' => 'BOGOTA',
        'ubicacion_origen' => 'SALA_DE_GARANTIA',
    ]);

    // Equipos sin traslado RECIBIDO no aparecen en /inventario.
    $this->getJson('/api/inventario?search=B25702')->assertOk()->assertJsonPath('meta.total', 0);
});

it('omite en la importación Cubit los equipos que ya están en el destino', function () {
    $traslado = ($this->crearTraslado)('BOGOTA', 'SALA_DE_GARANTIA');

    $archivo = ($this->xlsxCubit)([
        ['Reporte de Traslado de Elementos'],
        [],
        [],
        ['Identificador 1', 'Identificador 2', 'Ref. Principal', 'Descripción', 'Zona Actual', 'Ubicación Actual'],
        ['B257100', 'PWTEST01', 'PORTATIL LENOVO L14', 'PORTATIL', 'BOGOTA', '30052001965-ALMACEN'],
        ['B257101', 'PWTEST02', 'PORTATIL LENOVO L14', 'PORTATIL', 'BOGOTA', '30052001965-SALA_DE_GARANTIA'],
    ]);

    $this->postJson("/api/traslados/{$traslado['id']}/equipos/importar", ['archivo' => $archivo])
        ->assertOk()
        ->assertJsonPath('data.agregados', 1)
        ->assertJsonPath('data.errores.0.placa', 'B257101')
        ->assertJsonPath('data.errores.0.error', 'Operación denegada (TC-05): El equipo con placa B257101 ya se encuentra físicamente en el destino seleccionado (SALA DE GARANTIA).');

    $this->assertDatabaseHas('traslado_equipos', [
        'traslado_id' => $traslado['id'],
        'placa_id' => 'B257100',
    ]);

    $this->assertDatabaseMissing('traslado_equipos', [
        'traslado_id' => $traslado['id'],
        'placa_id' => 'B257101',
    ]);
});

it('bloquea el cierre de recepción mientras falten equipos por verificar', function () {
    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-K-1');
    ($this->inventario)('AF-K-2');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-K-1'])->assertCreated();
    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-K-2'])->assertCreated();
    $this->postJson("/api/traslados/{$traslado['id']}/despachar")->assertOk();

    Sanctum::actingAs($this->receptor);
    $this->postJson("/api/traslados/{$traslado['id']}/equipos/verificar", ['placa_id' => 'AF-K-1'])->assertOk();

    $this->postJson("/api/traslados/{$traslado['id']}/recibir")
        ->assertStatus(422)
        ->assertJsonValidationErrors('traslado');

    $this->assertDatabaseHas('traslados', ['id' => $traslado['id'], 'estado' => 'EN_TRANSITO']);
});

it('recibe con el 100% verificado y publica los equipos en inventario', function () {
    $this->creador->givePermissionTo('inventario.ver');
    $this->receptor->givePermissionTo('inventario.ver');

    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-RECIBE-1');
    ($this->inventario)('AF-RECIBE-2');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-RECIBE-1'])->assertCreated();
    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-RECIBE-2'])->assertCreated();
    $this->postJson("/api/traslados/{$traslado['id']}/despachar")->assertOk();

    Sanctum::actingAs($this->receptor);
    $this->postJson("/api/traslados/{$traslado['id']}/equipos/verificar", ['placa_id' => 'AF-RECIBE-1'])->assertOk();
    $this->postJson("/api/traslados/{$traslado['id']}/equipos/verificar", ['placa_id' => 'AF-RECIBE-2'])->assertOk();

    $this->postJson("/api/traslados/{$traslado['id']}/recibir")
        ->assertOk()
        ->assertJsonPath('data.estado', 'RECIBIDO');

    foreach (['AF-RECIBE-1', 'AF-RECIBE-2'] as $placa) {
        $this->assertDatabaseHas('inventario_general', [
            'placa_id' => $placa,
            'zona_actual' => 'BOGOTA',
            'ubicacion_actual' => 'SALA_DE_GARANTIA',
            'num_traslado' => $traslado['codigo'],
        ]);
    }

    $this->getJson('/api/inventario?search=AF-RECIBE')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.num_traslado', $traslado['codigo']);
});

it('permite a un administrador revertir el despacho a abierto', function () {
    Role::findOrCreate('admin');

    $admin = User::factory()->create(['name' => 'Admin Traslados']);
    $admin->assignRole('admin');
    $admin->givePermissionTo('traslados.registrar');

    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-REVERTIR');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-REVERTIR'])->assertCreated();
    $this->postJson("/api/traslados/{$traslado['id']}/despachar")->assertOk();

    Sanctum::actingAs($this->receptor);
    $this->postJson("/api/traslados/{$traslado['id']}/equipos/verificar", ['placa_id' => 'AF-REVERTIR'])->assertOk();

    Sanctum::actingAs($admin);
    $this->postJson("/api/traslados/{$traslado['id']}/revertir-apertura")
        ->assertOk()
        ->assertJsonPath('data.estado', 'ABIERTO');

    $this->assertDatabaseHas('traslados', [
        'id' => $traslado['id'],
        'estado' => 'ABIERTO',
        'despachado_por_id' => null,
    ]);

    $this->assertDatabaseHas('traslado_equipos', [
        'traslado_id' => $traslado['id'],
        'placa_id' => 'AF-REVERTIR',
        'recibido' => false,
    ]);
});

it('impide revertir el despacho a un usuario sin rol admin', function () {
    Role::findOrCreate('admin');

    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-REVERTIR-403');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-REVERTIR-403'])->assertCreated();
    $this->postJson("/api/traslados/{$traslado['id']}/despachar")->assertOk();

    $this->postJson("/api/traslados/{$traslado['id']}/revertir-apertura")
        ->assertStatus(403);

    $this->assertDatabaseHas('traslados', ['id' => $traslado['id'], 'estado' => 'EN_TRANSITO']);
});

it('lista traslados paginados con filtros y conteo de equipos', function () {
    $abierto = ($this->crearTraslado)();
    ($this->inventario)('AF-LIST-1');
    $this->postJson("/api/traslados/{$abierto['id']}/equipos", ['placa_id' => 'AF-LIST-1'])->assertCreated();

    $respuesta = $this->getJson('/api/traslados')->assertOk();

    expect($respuesta->json('meta.total'))->toBe(1);
    expect($respuesta->json('data.0.equipos_total'))->toBe(1);
    expect($respuesta->json('data.0.equipos_recibidos'))->toBe(0);

    $filtrado = $this->getJson('/api/traslados?estado=RECIBIDO')->assertOk();
    expect($filtrado->json('meta.total'))->toBe(0);
});

it('devuelve el detalle del traslado con sus equipos aplanados', function () {
    $traslado = ($this->crearTraslado)();
    ($this->inventario)('AF-DETALLE');

    $this->postJson("/api/traslados/{$traslado['id']}/equipos", ['placa_id' => 'AF-DETALLE'])->assertCreated();

    $respuesta = $this->getJson("/api/traslados/{$traslado['id']}")->assertOk();

    expect($respuesta->json('data.traslado.id'))->toBe($traslado['id']);
    expect($respuesta->json('data.equipos.0.placa_id'))->toBe('AF-DETALLE');
});

it('devuelve la ubicación actual y el traslado activo al escanear la placa', function () {
    ($this->inventario)('AF-BUSCAR', 'Almacén', 'ALMACEN');

    $respuesta = $this->getJson('/api/traslados/buscar/AF-BUSCAR')->assertOk();

    expect($respuesta->json('data.zona_actual'))->toBe('Almacén');
    expect($respuesta->json('data.ubicacion_actual'))->toBe('ALMACEN');
    expect($respuesta->json('data.traslado_activo'))->toBeNull();
});
