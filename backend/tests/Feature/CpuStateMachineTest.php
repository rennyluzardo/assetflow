<?php

use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([
        'cpus.ver_bitacora',
        'cpus.registrar',
        'soplado.ver_bitacora',
        'soplado.registrar',
    ] as $permiso) {
        Permission::findOrCreate($permiso);
    }

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
    'Novedad limpia -> Completado' => ['NOVEDAD', ['requiere_soplado_o_pasta' => false], 'DBAN', 'COMPLETADO', true],
    'Novedad sucia -> Por soplar' => ['NOVEDAD', ['requiere_soplado_o_pasta' => true], 'SALA_DE_GARANTIA', 'POR_SOPLAR', false],
    'Diagnóstico no funcional sin garantía -> Baja' => ['DIAGNOSTICO', ['esta_funcional' => false, 'tiene_garantia' => false], 'SALA_DE_BAJA', 'BAJA_NO_SIRVE', true],
    'Diagnóstico no funcional con garantía -> Pendiente garantía' => ['DIAGNOSTICO', ['esta_funcional' => false, 'tiene_garantia' => true], 'SALA_DE_GARANTIA', 'PENDIENTE_GARANTIA', false],
    'Diagnóstico funcional obsoleta -> Baja' => ['DIAGNOSTICO', ['esta_funcional' => true, 'es_obsoleta' => true], 'SALA_DE_BAJA', 'BAJA_NO_SIRVE', true],
    'Diagnóstico funcional no obsoleta limpia -> Completado' => ['DIAGNOSTICO', ['esta_funcional' => true, 'es_obsoleta' => false, 'requiere_soplado_o_pasta' => false], 'ALMACEN', 'COMPLETADO', true],
    'Diagnóstico funcional no obsoleta sucia -> Por soplar' => ['DIAGNOSTICO', ['esta_funcional' => true, 'es_obsoleta' => false, 'requiere_soplado_o_pasta' => true], 'SALA_DE_GARANTIA', 'POR_SOPLAR', false],
    'Intervención sin pieza -> Por reacondicionar' => ['INTERVENCION', ['hay_pieza' => false], 'SALA_DE_GARANTIA', 'POR_REACONDICIONAR', false],
    'Intervención con pieza sin soplado -> Intervenido' => ['INTERVENCION', ['hay_pieza' => true, 'requiere_soplado_o_pasta' => false], 'SALA_DE_GARANTIA', 'INTERVENIDO', true],
    'Intervención con pieza y soplado -> Por soplar' => ['INTERVENCION', ['hay_pieza' => true, 'requiere_soplado_o_pasta' => true], 'SALA_DE_GARANTIA', 'POR_SOPLAR', false],
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
    'novedad sucia a DBAN' => [['tipo_gestion' => 'NOVEDAD', 'requiere_soplado_o_pasta' => true, 'ubicacion_destino' => 'DBAN']],
    'diagnóstico no funcional sin garantía a ALMACEN' => [['tipo_gestion' => 'DIAGNOSTICO', 'esta_funcional' => false, 'tiene_garantia' => false, 'ubicacion_destino' => 'ALMACEN']],
    'intervención sin pieza a DBAN' => [['tipo_gestion' => 'INTERVENCION', 'hay_pieza' => false, 'ubicacion_destino' => 'DBAN']],
    'diagnóstico obsoleta a ALMACEN' => [['tipo_gestion' => 'DIAGNOSTICO', 'esta_funcional' => true, 'es_obsoleta' => true, 'ubicacion_destino' => 'ALMACEN']],
]);

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
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
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
        'ubicacion_destino' => 'SALA_DE_GARANTIA',
    ])->assertCreated();

    $this->postJson('/api/equipos/transicionar', [
        'placa_id' => 'AF-G-422',
        'ubicacion_destino' => 'SALA_DE_BAJA',
    ])->assertStatus(422)->assertJsonValidationErrors('ubicacion_destino');
});
