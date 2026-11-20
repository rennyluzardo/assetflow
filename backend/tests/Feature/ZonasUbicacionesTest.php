<?php

use App\Models\Ubicacion;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('zonas_ubicaciones.gestionar');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->admin = User::factory()->create(['name' => 'Administrador Zonas']);
    $this->admin->givePermissionTo('zonas_ubicaciones.gestionar');

    $this->analista = User::factory()->create(['name' => 'Analista Sin Permiso']);
});

function zonaConUbicaciones(string $sede = 'COLOMBIA', string $nombre = 'BOGOTA'): Zona
{
    $zona = Zona::create(['sede' => $sede, 'nombre' => $nombre, 'activo' => true]);

    Ubicacion::create(['zona_id' => $zona->id, 'nombre' => 'ALMACEN', 'activo' => true]);
    Ubicacion::create(['zona_id' => $zona->id, 'nombre' => 'SALA_DE_BAJA', 'activo' => false]);

    return $zona;
}

it('lista las zonas activas con sus ubicaciones activas a cualquier usuario autenticado', function () {
    zonaConUbicaciones();

    Sanctum::actingAs($this->analista);

    $respuesta = $this->getJson('/api/zonas')->assertOk();

    expect($respuesta->json('data.0.nombre'))->toBe('BOGOTA');

    $ubicaciones = collect($respuesta->json('data.0.ubicaciones'))->pluck('nombre')->all();
    expect($ubicaciones)->toContain('ALMACEN');
    expect($ubicaciones)->not->toContain('SALA_DE_BAJA');
});

it('bloquea a un usuario sin permiso la gestión de zonas y ubicaciones', function () {
    Sanctum::actingAs($this->analista);

    $this->getJson('/api/zonas-ubicaciones')->assertStatus(403);
    $this->postJson('/api/zonas', ['sede' => 'COLOMBIA', 'nombre' => 'CALI'])->assertStatus(403);
});

it('crea una zona como administrador', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/zonas', ['sede' => 'COLOMBIA', 'nombre' => 'CALI'])
        ->assertCreated()
        ->assertJsonPath('data.nombre', 'CALI')
        ->assertJsonPath('data.ubicaciones', []);

    $this->assertDatabaseHas('zonas', ['sede' => 'COLOMBIA', 'nombre' => 'CALI', 'activo' => true]);
});

it('crea una zona con el admin sembrado por migrate:fresh --seed', function () {
    // Reproduce el escenario reportado: base recién sembrada con DatabaseSeeder
    // (equivalente a `php artisan migrate:fresh --seed`) y sesión del admin
    // sembrado, sin preparar permisos a mano.
    $this->seed();

    $admin = User::where('email', 'admin@assetflow.local')->firstOrFail();
    Sanctum::actingAs($admin);

    $this->postJson('/api/zonas', ['sede' => 'COLOMBIA', 'nombre' => 'CALI'])
        ->assertCreated()
        ->assertJsonPath('data.sede', 'COLOMBIA')
        ->assertJsonPath('data.nombre', 'CALI')
        ->assertJsonPath('data.ubicaciones', []);

    $this->assertDatabaseHas('zonas', ['sede' => 'COLOMBIA', 'nombre' => 'CALI', 'activo' => true]);
});

it('devuelve el detalle de validación (422) cuando falta la sede', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/zonas', ['nombre' => 'SIN SEDE'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('sede')
        ->assertJsonPath('message', 'The sede field is required.');
});

it('rechaza duplicar el nombre de zona dentro de la misma sede', function () {
    zonaConUbicaciones();
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/zonas', ['sede' => 'COLOMBIA', 'nombre' => 'BOGOTA'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('nombre');

    // Mismo nombre en otra sede es válido.
    $this->postJson('/api/zonas', ['sede' => 'GUYANA', 'nombre' => 'BOGOTA'])
        ->assertCreated();
});

it('actualiza una zona y alterna su estado de actividad', function () {
    $zona = zonaConUbicaciones();
    Sanctum::actingAs($this->admin);

    $this->putJson("/api/zonas/{$zona->id}", ['sede' => 'COLOMBIA', 'nombre' => 'BOGOTA DC'])
        ->assertOk()
        ->assertJsonPath('data.nombre', 'BOGOTA DC');

    $this->patchJson("/api/zonas/{$zona->id}/inactivar")->assertOk()->assertJsonPath('data.activo', false);
    $this->patchJson("/api/zonas/{$zona->id}/activar")->assertOk()->assertJsonPath('data.activo', true);
});

it('crea una ubicación anidada bajo una zona sin exigir zona_id en el payload', function () {
    $zona = zonaConUbicaciones();
    Sanctum::actingAs($this->admin);

    $this->postJson("/api/zonas/{$zona->id}/ubicaciones", [
        'nombre' => 'SALA_DE_GARANTIA',
    ])->assertCreated()->assertJsonPath('data.nombre', 'SALA_DE_GARANTIA');

    $this->assertDatabaseHas('ubicaciones', [
        'zona_id' => $zona->id,
        'nombre' => 'SALA_DE_GARANTIA',
    ]);
});

it('actualiza una ubicación y alterna su estado de actividad', function () {
    $zona = zonaConUbicaciones();
    $ubicacion = $zona->ubicaciones()->where('nombre', 'ALMACEN')->first();
    Sanctum::actingAs($this->admin);

    $this->putJson("/api/ubicaciones/{$ubicacion->id}", [
        'zona_id' => $zona->id,
        'nombre' => 'ALMACEN GENERAL',
    ])->assertOk()->assertJsonPath('data.nombre', 'ALMACEN GENERAL');

    $this->patchJson("/api/ubicaciones/{$ubicacion->id}/inactivar")->assertOk()->assertJsonPath('data.activo', false);
    $this->patchJson("/api/ubicaciones/{$ubicacion->id}/activar")->assertOk()->assertJsonPath('data.activo', true);
});

it('rechaza duplicar el nombre de ubicación dentro de la misma zona', function () {
    $zona = zonaConUbicaciones();
    Sanctum::actingAs($this->admin);

    $this->postJson("/api/zonas/{$zona->id}/ubicaciones", ['nombre' => 'ALMACEN'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('nombre');
});

it('devuelve la vista de gestión con todas las ubicaciones (activas e inactivas)', function () {
    zonaConUbicaciones();
    Sanctum::actingAs($this->admin);

    $respuesta = $this->getJson('/api/zonas-ubicaciones')->assertOk();

    $ubicaciones = collect($respuesta->json('data.0.ubicaciones'))->pluck('nombre')->all();
    expect($ubicaciones)->toContain('ALMACEN');
    expect($ubicaciones)->toContain('SALA_DE_BAJA');
});
