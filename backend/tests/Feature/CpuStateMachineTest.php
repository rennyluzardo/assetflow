<?php

use App\Enums\EstadoEquipoEnum;
use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    foreach ([
        'cpus.ver_bitacora',
        'cpus.registrar',
        'soplado.ver_bitacora',
        'soplado.registrar',
    ] as $permiso) {
        Permission::findOrCreate($permiso);
    }

    Role::findOrCreate('admin');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->analista = User::factory()->create([
        'name' => 'Analista de Prueba',
        'email' => 'analista.prueba@assetflow.test',
    ]);

    $this->analista->givePermissionTo([
        'cpus.ver_bitacora',
        'cpus.registrar',
        'soplado.ver_bitacora',
        'soplado.registrar',
    ]);

    Sanctum::actingAs($this->analista);
});

it('resuelve todas las ramas del árbol de decisión', function (
    string $tipo,
    array $respuestas,
    string $destino,
    string $estadoEsperado,
    bool $intervenido,
) {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-RAMA',
        'usuario_cargue' => $this->analista->id,
    ]);

    $payload = [
        'placa_id' => 'AF-RAMA',
        'tipo_gestion' => $tipo,
        'ubicacion_destino' => $destino,
        'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg'),
    ] + $respuestas;

    $respuesta = $this->postJson('/api/equipos', $payload);

    $respuesta->assertCreated();
    expect($respuesta->json('data.estado'))->toBe($estadoEsperado);
    expect($respuesta->json('data.ubicacion_destino'))->toBe($destino);

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-RAMA',
        'estado_actual' => $estadoEsperado,
        'intervenido' => $intervenido,
    ]);
})->with([
    'Novedad limpia -> Completado' => ['NOVEDAD', ['requiere_soplado_o_pasta' => false, 'observaciones' => 'Novedad menor.'], 'DBAN', 'COMPLETADO', true],
    'Novedad sucia -> Por soplar' => ['NOVEDAD', ['requiere_soplado_o_pasta' => true, 'observaciones' => 'Acumulación de polvo.'], 'SALA_DE_GARANTIA', 'POR_SOPLAR', false],
    'Diagnóstico no funcional sin garantía -> Baja' => ['DIAGNOSTICO', ['esta_funcional' => false, 'tiene_garantia' => false, 'motivo_baja' => 'Quemada', 'serial_disco' => 'SD-1'], 'SALA_DE_BAJA', 'BAJA_NO_SIRVE', true],
    'Diagnóstico no funcional con garantía -> Pendiente garantía' => ['DIAGNOSTICO', ['esta_funcional' => false, 'tiene_garantia' => true, 'ticket_garantia' => 'TK-CPU', 'pieza_garantia' => 'Board'], 'SALA_DE_GARANTIA', 'PENDIENTE_GARANTIA', false],
    'Diagnóstico funcional limpia -> Completado' => ['DIAGNOSTICO', ['esta_funcional' => true, 'requiere_soplado_o_pasta' => false], 'ALMACEN', 'COMPLETADO', true],
    'Diagnóstico funcional sucia -> Por soplar' => ['DIAGNOSTICO', ['esta_funcional' => true, 'requiere_soplado_o_pasta' => true], 'SALA_DE_GARANTIA', 'POR_SOPLAR', false],
    'Intervención sin pieza -> Por reacondicionar' => ['INTERVENCION', ['hay_pieza' => false], 'SALA_DE_GARANTIA', 'POR_REACONDICIONAR', false],
    'Intervención con pieza sin soplado -> Intervenido' => ['INTERVENCION', ['hay_pieza' => true, 'requiere_soplado_o_pasta' => false], 'SALA_DE_GARANTIA', 'INTERVENIDO', true],
    'Intervención con pieza y soplado -> Por soplar' => ['INTERVENCION', ['hay_pieza' => true, 'requiere_soplado_o_pasta' => true], 'SALA_DE_GARANTIA', 'POR_SOPLAR', false],
    'Baja no funcional (daño) -> Baja' => ['BAJA', ['esta_funcional' => false, 'motivo_baja' => 'Quemada', 'serial_disco' => 'SD-2'], 'SALA_DE_BAJA', 'BAJA_NO_SIRVE', true],
    'Baja funcional (vida útil) -> Baja auto OBSOLETA' => ['BAJA', ['esta_funcional' => true, 'serial_disco' => 'SD-3'], 'SALA_DE_BAJA', 'BAJA_NO_SIRVE', true],
]);

it('rechaza con 422 una ubicación de destino ilegal para la rama', function (array $payload) {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-422',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', ['placa_id' => 'AF-422'] + $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('ubicacion_destino');
})->with([
    'novedad sucia a DBAN' => [['tipo_gestion' => 'NOVEDAD', 'requiere_soplado_o_pasta' => true, 'observaciones' => 'x', 'ubicacion_destino' => 'DBAN', 'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg')]],
    'diagnóstico no funcional sin garantía a ALMACEN' => [['tipo_gestion' => 'DIAGNOSTICO', 'esta_funcional' => false, 'tiene_garantia' => false, 'motivo_baja' => 'x', 'serial_disco' => 'SD', 'ubicacion_destino' => 'ALMACEN', 'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg')]],
    'intervención sin pieza a DBAN' => [['tipo_gestion' => 'INTERVENCION', 'hay_pieza' => false, 'ubicacion_destino' => 'DBAN', 'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg')]],
    'baja a ALMACEN' => [['tipo_gestion' => 'BAJA', 'esta_funcional' => false, 'motivo_baja' => 'x', 'serial_disco' => 'SD', 'ubicacion_destino' => 'ALMACEN']],
]);

it('exige observaciones en NOVEDAD', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-NOV-OBS',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-NOV-OBS',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => false,
        'ubicacion_destino' => 'ALMACEN',
    ])->assertStatus(422)->assertJsonValidationErrors('observaciones');
});

it('exige motivo_baja y serial_disco en la baja de diagnóstico sin garantía', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-DX-BAJA',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-DX-BAJA',
        'tipo_gestion' => 'DIAGNOSTICO',
        'esta_funcional' => false,
        'tiene_garantia' => false,
        'ubicacion_destino' => 'SALA_DE_BAJA',
    ])->assertStatus(422)->assertJsonValidationErrors(['motivo_baja', 'serial_disco']);
});

it('exige ticket_garantia y pieza_garantia en el diagnóstico con garantía', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-DX-GAR',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-DX-GAR',
        'tipo_gestion' => 'DIAGNOSTICO',
        'esta_funcional' => false,
        'tiene_garantia' => true,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertStatus(422)->assertJsonValidationErrors(['ticket_garantia', 'pieza_garantia']);
});

it('fija automáticamente es_obsoleta y motivo_baja OBSOLETA en la baja por vida útil', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-BAJA-OBS',
        'usuario_cargue' => $this->analista->id,
    ]);

    $respuesta = $this->postJson('/api/equipos', [
        'placa_id' => 'AF-BAJA-OBS',
        'tipo_gestion' => 'BAJA',
        'esta_funcional' => true,
        'serial_disco' => 'SD-OBS',
        'ubicacion_destino' => 'SALA_DE_BAJA',
    ])->assertCreated();

    expect($respuesta->json('data.estado'))->toBe('BAJA_NO_SIRVE');

    $this->assertDatabaseHas('equipos', [
        'placa_id' => 'AF-BAJA-OBS',
        'es_obsoleta' => true,
        'motivo_baja' => 'OBSOLETA',
    ]);
});

it('busca el equipo por placa_id y devuelve el traslado automáticamente', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-BUSCAR',
        'usuario_cargue' => $this->analista->id,
        'num_traslado' => 'TR-888',
        'referencia' => 'OptiPlex 7050',
    ]);

    $respuesta = $this->getJson('/api/equipos/buscar/AF-BUSCAR');

    $respuesta->assertOk();
    expect($respuesta->json('data.placa_id'))->toBe('AF-BUSCAR');
    expect($respuesta->json('data.num_traslado'))->toBe('TR-888');
    expect($respuesta->json('data.referencia'))->toBe('OptiPlex 7050');
});

it('cierra una garantía pendiente hacia Completado', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-GARANTIA',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-GARANTIA',
        'tipo_gestion' => 'DIAGNOSTICO',
        'esta_funcional' => false,
        'tiene_garantia' => true,
        'ticket_garantia' => 'TK-1',
        'pieza_garantia' => 'Board',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
        'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg'),
    ])->assertCreated();

    $respuesta = $this->postJson('/api/equipos/transicionar', [
        'placa_id' => 'AF-GARANTIA',
        'ubicacion_destino' => 'ALMACEN',
    ]);

    $respuesta->assertOk();
    expect($respuesta->json('data.estado'))->toBe('COMPLETADO');

    $this->assertDatabaseHas('inventario_general', [
        'placa_id' => 'AF-GARANTIA',
        'intervenido' => true,
    ]);
});

it('reanuda un equipo por reacondicionar hacia Intervenido o Por soplar', function (bool $requiereSoplado, string $estadoEsperado) {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-REACOND',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-REACOND',
        'tipo_gestion' => 'INTERVENCION',
        'hay_pieza' => false,
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
        'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg'),
    ])->assertCreated();

    $respuesta = $this->postJson('/api/equipos/transicionar', [
        'placa_id' => 'AF-REACOND',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
        'requiere_soplado_o_pasta' => $requiereSoplado,
    ]);

    $respuesta->assertOk();
    expect($respuesta->json('data.estado'))->toBe($estadoEsperado);
})->with([
    'sin soplado -> Intervenido' => [false, 'INTERVENIDO'],
    'con soplado -> Por soplar' => [true, 'POR_SOPLAR'],
]);

it('rechaza un cierre de garantía con destino no permitido', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-G-422',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-G-422',
        'tipo_gestion' => 'DIAGNOSTICO',
        'esta_funcional' => false,
        'tiene_garantia' => true,
        'ticket_garantia' => 'TK-1',
        'pieza_garantia' => 'Board',
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
        'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg'),
    ])->assertCreated();

    $this->postJson('/api/equipos/transicionar', [
        'placa_id' => 'AF-G-422',
        'ubicacion_destino' => 'SALA_DE_BAJA',
    ])->assertStatus(422)->assertJsonValidationErrors('ubicacion_destino');
});

it('bloquea una nueva gestión sobre un activo ya intervenido sin rol admin', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-REINT',
        'intervenido' => true,
        'estado_actual' => EstadoEquipoEnum::COMPLETADO,
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-REINT',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => false,
        'observaciones' => 'Re-intervención',
        'ubicacion_destino' => 'ALMACEN',
        'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg'),
    ])->assertStatus(422)->assertJsonValidationErrors('placa_id');
});

it('permite una nueva gestión sobre un activo intervenido con rol admin', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-REINT-ADMIN',
        'intervenido' => true,
        'estado_actual' => EstadoEquipoEnum::COMPLETADO,
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->analista->assignRole('admin');

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-REINT-ADMIN',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => false,
        'observaciones' => 'Re-intervención autorizada',
        'ubicacion_destino' => 'ALMACEN',
        'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg'),
    ])->assertCreated();
});

it('adjunta evidencia fotográfica a un registro de CPU', function () {
    Storage::fake('public');

    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-FOTO',
        'usuario_cargue' => $this->analista->id,
    ]);

    $respuesta = $this->postJson('/api/equipos', [
        'placa_id' => 'AF-FOTO',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => false,
        'observaciones' => 'Con evidencia fotográfica',
        'ubicacion_destino' => 'ALMACEN',
        'foto_equipo' => UploadedFile::fake()->image('evidencia.jpg', 120, 120),
    ]);

    $respuesta->assertCreated();

    $foto = $respuesta->json('data.foto_equipo');
    expect($foto)->not->toBeNull();
    expect($foto)->toStartWith('equipos/');

    Storage::disk('public')->assertExists($foto);
});

it('rechaza un archivo que no es imagen como evidencia fotográfica', function () {
    Storage::fake('public');

    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-FOTO-BAD',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-FOTO-BAD',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => false,
        'observaciones' => 'Evidencia inválida',
        'ubicacion_destino' => 'ALMACEN',
        'foto_equipo' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors('foto_equipo');
});

it('rechaza con 422 el registro sin evidencia fotográfica en gestiones que la exigen', function (
    string $tipo,
    array $respuestas,
    string $destino,
) {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-FOTO-REQ',
        'usuario_cargue' => $this->analista->id,
    ]);

    $respuesta = $this->postJson('/api/equipos', [
        'placa_id' => 'AF-FOTO-REQ',
        'tipo_gestion' => $tipo,
        'ubicacion_destino' => $destino,
    ] + $respuestas);

    $respuesta->assertStatus(422)->assertJsonValidationErrors('foto_equipo');
    expect($respuesta->json('errors.foto_equipo'))->toContain(
        'La evidencia fotográfica del equipo es obligatoria para Novedad, Diagnóstico e Intervención.',
    );
})->with([
    'NOVEDAD sin foto' => ['NOVEDAD', ['requiere_soplado_o_pasta' => false, 'observaciones' => 'Novedad menor.'], 'ALMACEN'],
    'DIAGNOSTICO sin foto' => ['DIAGNOSTICO', ['esta_funcional' => true, 'requiere_soplado_o_pasta' => false], 'ALMACEN'],
    'INTERVENCION sin foto' => ['INTERVENCION', ['hay_pieza' => false], 'SALA_DE_GARANTIA'],
]);

it('permite registrar BAJA sin evidencia fotográfica', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-FOTO-BAJA',
        'usuario_cargue' => $this->analista->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-FOTO-BAJA',
        'tipo_gestion' => 'BAJA',
        'esta_funcional' => true,
        'serial_disco' => 'SD-FOTO-BAJA',
        'ubicacion_destino' => 'SALA_DE_BAJA',
    ])->assertCreated();
});
