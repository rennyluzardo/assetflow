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

    foreach ([
        'cpus.registrar',
        'soplado.registrar',
        'portatiles.registrar',
        'trazabilidad.ver',
    ] as $permiso) {
        Permission::findOrCreate($permiso);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->analista = User::factory()->create([
        'name' => 'Analista de Prueba',
        'email' => 'analista.trazabilidad@assetflow.test',
    ]);

    $this->analista->givePermissionTo([
        'cpus.registrar',
        'soplado.registrar',
        'portatiles.registrar',
        'trazabilidad.ver',
    ]);

    Sanctum::actingAs($this->analista);
});

it('unifica la línea de tiempo de CPU y soplado en orden cronológico', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-TRZ-CPU',
        'serial' => 'SN-TRZ-CPU',
        'usuario_cargue' => $this->analista->id,
        'num_traslado' => 'TR-123',
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-TRZ-CPU',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => true,
        'observaciones' => 'Novedad con acumulación de polvo.',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
        'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg'),
    ])->assertCreated();

    $this->post('/api/soplado', [
        'placa_id' => 'AF-TRZ-CPU',
        'energiza' => true,
        'da_video' => true,
        'acceso_bios' => true,
        'tipo_suciedad' => 'polvo',
        'aplica_pasta_termica' => true,
        'foto_ruta' => UploadedFile::fake()->image('evidencia.jpg'),
        'ubicacion_destino' => 'ALMACEN',
    ], ['Accept' => 'application/json'])->assertCreated();

    $respuesta = $this->getJson('/api/trazabilidad?q=AF-TRZ-CPU');

    $respuesta->assertOk();
    expect($respuesta->json('activo.placa_id'))->toBe('AF-TRZ-CPU');
    expect($respuesta->json('activo.num_traslado'))->toBe('TR-123');
    expect($respuesta->json('activo.estado_actual'))->toBe('COMPLETADO');

    $eventos = $respuesta->json('eventos');
    expect($eventos)->toHaveCount(3);
    expect(collect($eventos)->pluck('tipo')->sort()->values()->all())
        ->toBe(['equipos', 'inventario', 'soplado']);

    $soplado = collect($eventos)->firstWhere('tipo', 'soplado');
    expect($soplado['analista'])->toBe('Analista de Prueba');
    expect($soplado['foto'])->toStartWith('soplado/');

    // Cada hito expone sus evidencias normalizadas a URL pública.
    expect($soplado['fotos'])->toHaveCount(1);
    expect($soplado['fotos'][0])->toStartWith('/storage/soplado/');

    // Las observaciones técnicas del diagnóstico de CPU se exponen tal cual.
    $equipo = collect($eventos)->firstWhere('tipo', 'equipos');
    expect($equipo['observaciones'])->toBe('Novedad con acumulación de polvo.');
    expect($equipo['fotos'])->toHaveCount(1);
    expect($equipo['fotos'][0])->toStartWith('/storage/equipos/');

    // Los hitos sin evidencia fotográfica devuelven un arreglo vacío.
    $ingreso = collect($eventos)->firstWhere('tipo', 'inventario');
    expect($ingreso['fotos'])->toBe([]);

    // El evento de soplado expone la nota técnica derivada de la inspección.
    expect($soplado['observaciones'])->toContain('Suciedad: Polvo');
    expect($soplado['observaciones'])->toContain('Pasta térmica: Aplicada');

    // La línea de tiempo retorna las fechas en orden ascendente.
    $fechas = array_map(fn (array $e) => $e['fecha'], $eventos);
    $ordenadas = $fechas;
    sort($ordenadas);
    expect($fechas)->toBe($ordenadas);
});

it('incluye el evento de portátil y permite buscar por serial', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-TRZ-LAP',
        'serial' => 'SN-TRZ-LAP',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-TRZ-LAP',
        'clasificacion' => 'FUNCIONAL',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'ALMACEN',
    ])->assertCreated();

    $respuesta = $this->getJson('/api/trazabilidad?q=SN-TRZ-LAP');

    $respuesta->assertOk();
    expect($respuesta->json('activo.placa_id'))->toBe('AF-TRZ-LAP');
    expect($respuesta->json('activo.serial'))->toBe('SN-TRZ-LAP');

    $portatil = collect($respuesta->json('eventos'))->firstWhere('tipo', 'portatil');
    expect($portatil['estado'])->toBe('COMPLETADO');
    expect($portatil['estacion'])->toBe('Portátil');
    expect($portatil['detalle'])->toContain('Funcional');
    expect($portatil['fotos'])->toBe([]);
});

it('expone la evidencia fotográfica del portátil cuando existe', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-TRZ-LAP-FOTO',
        'serial' => 'SN-TRZ-LAP-FOTO',
        'usuario_cargue' => $this->analista->id,
    ]);

    $registro = $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-TRZ-LAP-FOTO',
        'clasificacion' => 'FUNCIONAL',
        'test_lenovo' => true,
        'ubicacion_destino' => 'ALMACEN',
    ])->assertCreated()->json('data');

    $this->post("/api/portatiles/{$registro['id']}/foto", [
        'foto_evidencia' => UploadedFile::fake()->image('evidencia.jpg'),
    ], ['Accept' => 'application/json'])->assertOk();

    $portatil = collect($this->getJson('/api/trazabilidad?q=AF-TRZ-LAP-FOTO')->json('eventos'))
        ->firstWhere('tipo', 'portatil');

    expect($portatil['fotos'])->toHaveCount(1);
    expect($portatil['fotos'][0])->toStartWith('/storage/portatiles/');
});

it('sirve las evidencias fotográficas desde el disco público', function () {
    Storage::disk('public')->put('equipos/test.jpg', 'contenido-imagen');

    $this->get('/storage/equipos/test.jpg')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=86400, public')
        ->assertStreamedContent('contenido-imagen');

    $this->get('/storage/equipos/no-existe.jpg')->assertNotFound();

    // La ruta nunca debe escapar de la raíz del disco.
    $this->get('/storage/..%2F.env')->assertNotFound();
});

it('expone las observaciones técnicas exactas de un diagnóstico de portátil', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-TRZ-LAP-NOV',
        'serial' => 'SN-TRZ-LAP-NOV',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/portatiles', [
        'placa_id' => 'AF-TRZ-LAP-NOV',
        'clasificacion' => 'NOVEDAD',
        'test_lenovo' => true,
        'gravedad_novedad' => 'PRONUNCIADA',
        'cantidad_danos' => 2,
        'piezas_solicitar' => 'Pantalla 14" + teclado',
        'observaciones' => 'Bisagra y pantalla dañadas.',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $respuesta = $this->getJson('/api/trazabilidad?q=AF-TRZ-LAP-NOV');

    $respuesta->assertOk();

    $portatil = collect($respuesta->json('eventos'))->firstWhere('tipo', 'portatil');
    expect($portatil['estado'])->toBe('POR_REACONDICIONAR');
    expect($portatil['observaciones'])->toBe('Bisagra y pantalla dañadas.');
});
