<?php

use App\Enums\EstadoEquipoEnum;
use App\Enums\TipoGestionEnum;
use App\Enums\UbicacionDestinoEnum;
use App\Models\Equipo;
use App\Models\InventarioGeneral;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
});

it('inicia sesión con credenciales válidas y devuelve token, roles y permisos', function () {
    $respuesta = $this->postJson('/api/login', [
        'email' => 'admin@assetflow.local',
        'password' => 'password',
    ]);

    $respuesta->assertOk()
        ->assertJsonStructure(['token', 'user', 'roles', 'permissions'])
        ->assertJsonPath('user.email', 'admin@assetflow.local')
        ->assertJsonPath('roles.0', 'admin');

    expect($respuesta->json('token'))->toBeString()->not->toBeEmpty();
    expect($respuesta->json('permissions'))->toContain('inventario.ver');
});

it('rechaza credenciales inválidas con 422', function () {
    $this->postJson('/api/login', [
        'email' => 'admin@assetflow.local',
        'password' => 'incorrecta',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('devuelve el usuario autenticado con sus roles y permisos en /api/me', function () {
    $usuario = User::where('email', 'soplado@assetflow.local')->firstOrFail();
    Sanctum::actingAs($usuario);

    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.email', 'soplado@assetflow.local')
        ->assertJsonPath('roles.0', 'analista')
        ->assertJsonPath('permissions', fn (array $permisos) => in_array('soplado.registrar', $permisos, true));
});

it('bloquea con 401 las rutas protegidas sin token', function () {
    $this->getJson('/api/me')->assertStatus(401);
    $this->getJson('/api/equipos/buscar/AF-000')->assertStatus(401);
    $this->postJson('/api/soplado', [])->assertStatus(401);
});

it('toma el analista_id del token en /equipos e ignora el del payload', function () {
    $usuario = User::where('email', 'cpu@assetflow.local')->firstOrFail();
    Sanctum::actingAs($usuario);

    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-AUTH-EQ',
        'usuario_cargue' => $usuario->id,
    ]);

    $this->postJson('/api/equipos', [
        'placa_id' => 'AF-AUTH-EQ',
        'tipo_gestion' => 'NOVEDAD',
        'requiere_soplado_o_pasta' => false,
        'ubicacion_destino' => 'DBAN',
        'analista_id' => 999999,
    ])->assertCreated();

    $this->assertDatabaseHas('equipos', ['placa_id' => 'AF-AUTH-EQ', 'analista_id' => $usuario->id]);
    $this->assertDatabaseMissing('equipos', ['placa_id' => 'AF-AUTH-EQ', 'analista_id' => 999999]);
});

it('toma el analista_id del token en /soplado e ignora el del payload', function () {
    $usuario = User::where('email', 'soplado@assetflow.local')->firstOrFail();
    Sanctum::actingAs($usuario);

    $inventario = InventarioGeneral::factory()->create([
        'placa_id' => 'AF-AUTH-SOP',
        'usuario_cargue' => $usuario->id,
    ]);

    Equipo::create([
        'inventario_general_id' => $inventario->id,
        'placa_id' => $inventario->placa_id,
        'serial' => $inventario->serial,
        'descripcion' => $inventario->descripcion,
        'num_traslado' => $inventario->num_traslado,
        'tipo_gestion' => TipoGestionEnum::NOVEDAD,
        'requiere_soplado_o_pasta' => true,
        'ubicacion_destino' => UbicacionDestinoEnum::SALA_DE_GARANTIA,
        'estado' => EstadoEquipoEnum::POR_SOPLAR,
        'analista_id' => $usuario->id,
    ]);

    $this->post('/api/soplado', [
        'placa_id' => 'AF-AUTH-SOP',
        'energiza' => true,
        'da_video' => true,
        'acceso_bios' => true,
        'tipo_suciedad' => 'polvo',
        'aplica_pasta_termica' => true,
        'foto_ruta' => UploadedFile::fake()->image('evidencia.jpg'),
        'ubicacion_destino' => 'ALMACEN',
        'analista_id' => 999999,
    ], ['Accept' => 'application/json'])->assertStatus(201);

    $this->assertDatabaseHas('soplado_registros', ['analista_id' => $usuario->id]);
    $this->assertDatabaseMissing('soplado_registros', ['analista_id' => 999999]);
});
