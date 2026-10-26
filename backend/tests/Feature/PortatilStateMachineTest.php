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
    foreach (['portatiles.ver_bitacora', 'portatiles.registrar', 'trazabilidad.ver'] as $permiso) {
        Permission::findOrCreate($permiso);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->analista = User::factory()->create([
        'name' => 'Técnico de Portátiles',
        'email' => 'portatiles.prueba@assetflow.test',
    ]);

    $this->analista->givePermissionTo([
        'portatiles.ver_bitacora',
        'portatiles.registrar',
        'trazabilidad.ver',
    ]);

    Sanctum::actingAs($this->analista);
});

it('resuelve la matriz de clasificación de laptops', function (
    string $placa,
    array $clasificacionYCampos,
    string $destino,
    string $estadoEsperado,
    bool $intervenido,
) {
    InventarioGeneral::factory()->create([
        'placa_id' => $placa,
        'usuario_cargue' => $this->analista->id,
    ]);

    $payload = [
        'placa_id' => $placa,
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-'.$placa,
        'ubicacion_destino' => $destino,
    ] + $clasificacionYCampos;

    $respuesta = $this->postJson('/api/portatiles', $payload);

    $respuesta->assertCreated();
    expect($respuesta->json('data.estado'))->toBe($estadoEsperado);
    expect($respuesta->json('data.ubicacion_destino'))->toBe($destino);

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => $placa,
        'estado_actual' => $estadoEsperado,
        'intervenido' => $intervenido,
    ]);
})->with([
    'Funcional -> Completado en Almacén' => ['AF-LAP-FUNC', ['clasificacion' => 'FUNCIONAL'], 'ALMACEN', 'COMPLETADO', true],
    'Novedad mínima -> Completado en Almacén' => ['AF-LAP-MIN', ['clasificacion' => 'NOVEDAD', 'gravedad_novedad' => 'MINIMA'], 'ALMACEN', 'COMPLETADO', true],
    'Novedad pronunciada -> Por reacondicionar' => ['AF-LAP-PRON', ['clasificacion' => 'NOVEDAD', 'gravedad_novedad' => 'PRONUNCIADA', 'cantidad_danos' => 2, 'piezas_solicitar' => 'Bisagra inferior'], 'SALA_DE_GARANTIA', 'POR_REACONDICIONAR', false],
    'Garantía de fábrica -> Pendiente garantía' => ['AF-LAP-FAB', ['clasificacion' => 'GARANTIA', 'origen_dano_garantia' => 'FABRICA', 'ticket_proveedor' => 'TK-777'], 'SALA_DE_GARANTIA', 'PENDIENTE_GARANTIA', false],
    'Garantía inducida -> Por reacondicionar' => ['AF-LAP-IND', ['clasificacion' => 'GARANTIA', 'origen_dano_garantia' => 'INDUCIDO', 'piezas_solicitar' => 'Pantalla 14"'], 'SALA_DE_GARANTIA', 'POR_REACONDICIONAR', false],
    'Daño reparable -> Por reacondicionar' => ['AF-LAP-DANO', ['clasificacion' => 'DANO_REPARABLE', 'cantidad_danos' => 3, 'piezas_solicitar' => 'Teclado', 'partes_fru' => 'FRU-KBD'], 'SALA_DE_GARANTIA', 'POR_REACONDICIONAR', false],
    'Baja -> Baja no sirve' => ['AF-LAP-BAJA', ['clasificacion' => 'BAJA', 'causal_baja' => 'OBSOLETO'], 'SALA_DE_BAJA', 'BAJA_NO_SIRVE', true],
]);

it('rechaza con 422 una ubicación de destino ilegal para la rama', function (array $payload) {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-422',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-422',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
    ] + $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('ubicacion_destino');
})->with([
    'funcional a Sala de Baja' => [['clasificacion' => 'FUNCIONAL', 'ubicacion_destino' => 'SALA_DE_BAJA']],
    'baja a Almacén' => [['clasificacion' => 'BAJA', 'causal_baja' => 'OBSOLETO', 'ubicacion_destino' => 'ALMACEN']],
    'garantía de fábrica a Sala de Baja' => [['clasificacion' => 'GARANTIA', 'origen_dano_garantia' => 'FABRICA', 'ticket_proveedor' => 'TK-1', 'ubicacion_destino' => 'SALA_DE_BAJA']],
]);

it('rechaza una novedad pronunciada con un solo daño', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-PD1',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-PD1',
        'clasificacion' => 'NOVEDAD',
        'gravedad_novedad' => 'PRONUNCIADA',
        'cantidad_danos' => 1,
        'piezas_solicitar' => 'Bisagra',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertStatus(422)->assertJsonValidationErrors('cantidad_danos');
});

it('rechaza un daño reparable con más de tres daños', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-PD3',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-PD3',
        'clasificacion' => 'DANO_REPARABLE',
        'cantidad_danos' => 4,
        'piezas_solicitar' => 'Teclado',
        'partes_fru' => 'FRU-KBD',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertStatus(422)->assertJsonValidationErrors('cantidad_danos');
});

it('acepta un daño reparable sin código FRU de partes (partes_fru opcional)', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-SINFRU',
        'usuario_cargue' => $this->analista->id,
    ]);

    $respuesta = $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-SINFRU',
        'clasificacion' => 'DANO_REPARABLE',
        'cantidad_danos' => 2,
        'piezas_solicitar' => 'Teclado y bisagra',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    expect($respuesta->json('data.estado'))->toBe('POR_REACONDICIONAR');
    expect($respuesta->json('data.partes_fru'))->toBeNull();
});

it('cierra el registro único abierto hacia Almacén General', function (
    string $clasificacion,
    array $campos,
) {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-CIERRE',
        'usuario_cargue' => $this->analista->id,
    ]);

    $creado = $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-CIERRE',
        'clasificacion' => $clasificacion,
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ] + $campos)->assertCreated();

    $id = $creado->json('data.id');

    $cierre = $this->patchJson("/api/portatiles/{$id}/cerrar", [
        'ubicacion_destino' => 'ALMACEN',
        'reparado_por' => 'Taller TP',
    ]);

    $cierre->assertOk();
    expect($cierre->json('data.estado'))->toBe('INTERVENIDO');
    expect($cierre->json('data.ubicacion_destino'))->toBe('ALMACEN');
    expect($cierre->json('data.reparado_por'))->toBe('Taller TP');

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-LAP-CIERRE',
        'estado_actual' => 'INTERVENIDO',
        'intervenido' => true,
    ]);

    // Registro único: el cierre muta el mismo registro, no crea uno nuevo.
    $this->assertDatabaseCount('garantia_portatil', 1);
})->with([
    'desde pendiente garantía' => ['GARANTIA', ['origen_dano_garantia' => 'FABRICA', 'ticket_proveedor' => 'TK-1']],
    'desde por reacondicionar' => ['DANO_REPARABLE', ['cantidad_danos' => 2, 'piezas_solicitar' => 'Teclado', 'partes_fru' => 'FRU-KBD']],
]);

it('rechaza un cierre con destino distinto de Almacén', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-C-422',
        'usuario_cargue' => $this->analista->id,
    ]);

    $creado = $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-C-422',
        'clasificacion' => 'GARANTIA',
        'origen_dano_garantia' => 'FABRICA',
        'ticket_proveedor' => 'TK-1',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $this->patchJson('/api/portatiles/'.$creado->json('data.id').'/cerrar', [
        'ubicacion_destino' => 'SALA_DE_BAJA',
        'reparado_por' => 'Taller TP',
    ])->assertStatus(422)->assertJsonValidationErrors('ubicacion_destino');
});

it('rechaza cerrar un registro que ya está en estado terminal', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-TERM',
        'usuario_cargue' => $this->analista->id,
    ]);

    $creado = $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-TERM',
        'clasificacion' => 'FUNCIONAL',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'ALMACEN',
    ])->assertCreated();

    $this->patchJson('/api/portatiles/'.$creado->json('data.id').'/cerrar', [
        'ubicacion_destino' => 'ALMACEN',
        'reparado_por' => 'Taller TP',
    ])->assertStatus(422)->assertJsonValidationErrors('estado');
});

it('busca el portátil y devuelve su registro único abierto', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-BUSCAR',
        'usuario_cargue' => $this->analista->id,
        'num_traslado' => 'TR-99',
        'referencia' => 'ThinkPad T14',
    ]);

    $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-BUSCAR',
        'clasificacion' => 'GARANTIA',
        'origen_dano_garantia' => 'FABRICA',
        'ticket_proveedor' => 'TK-1',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $respuesta = $this->getJson('/api/portatiles/buscar/AF-LAP-BUSCAR');

    $respuesta->assertOk();
    expect($respuesta->json('data.placa_id'))->toBe('AF-LAP-BUSCAR');
    expect($respuesta->json('data.num_traslado'))->toBe('TR-99');
    expect($respuesta->json('data.portatil_activo.estado'))->toBe('PENDIENTE_GARANTIA');
});

it('adjunta evidencia fotográfica al registro con nombre UUID', function () {
    Storage::fake('public');

    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-FOTO',
        'usuario_cargue' => $this->analista->id,
    ]);

    $creado = $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-FOTO',
        'clasificacion' => 'FUNCIONAL',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'ALMACEN',
    ])->assertCreated();

    $respuesta = $this->post(
        '/api/portatiles/'.$creado->json('data.id').'/foto',
        ['foto_evidencia' => UploadedFile::fake()->image('evidencia.jpg')],
        ['Accept' => 'application/json'],
    );

    $respuesta->assertOk();
    expect($respuesta->json('data.foto_evidencia'))->toStartWith('portatiles/');
});

it('lista la bitácora paginada y filtra por clasificación', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-LAP-BIT',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-LAP-BIT',
        'clasificacion' => 'BAJA',
        'causal_baja' => 'DANO_TOTAL',
        'test_lenovo' => false,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'SALA_DE_BAJA',
    ])->assertCreated();

    $this->getJson('/api/portatiles?clasificacion=BAJA')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.clasificacion', 'BAJA');

    $this->getJson('/api/portatiles?clasificacion=FUNCIONAL')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});
