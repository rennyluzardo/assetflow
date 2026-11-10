<?php

use App\Models\GarantiaPortatil;
use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('dashboard.ver_solo_propio');
    Role::firstOrCreate(['name' => 'admin']);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('muestra métricas globales del taller para admin', function () {
    $admin = User::factory()->create(['name' => 'Administrador']);
    $admin->assignRole('admin');
    $admin->givePermissionTo('dashboard.ver_solo_propio');

    $a = User::factory()->create(['name' => 'Analista A']);
    $b = User::factory()->create(['name' => 'Analista B']);

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $a->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'COMPLETADO',
        'intervenido' => true,
        'fecha_intervencion' => now(),
    ]);

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $b->id,
        'modulo_intervencion' => 'portatiles',
        'estado_actual' => 'INTERVENIDO',
        'intervenido' => true,
        'fecha_intervencion' => now(),
    ]);

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $a->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'POR_SOPLAR',
        'intervenido' => false,
        'fecha_intervencion' => now(),
    ]);

    Sanctum::actingAs($admin);

    $respuesta = $this->getJson('/api/dashboard?periodo=anio')->assertOk();

    expect($respuesta->json('data.solo_propio'))->toBeFalse();
    expect($respuesta->json('data.kpis.total_inventario'))->toBe(3);
    expect($respuesta->json('data.kpis.cerrados'))->toBe(2);
    expect($respuesta->json('data.kpis.en_transito'))->toBe(1);
    expect($respuesta->json('data.kpis.intervenciones_periodo'))->toBe(3);
    expect(count($respuesta->json('data.productividad.por_analista')))->toBe(2);
});

it('aísla estrictamente las métricas para un usuario con dashboard.ver_solo_propio', function () {
    $a = User::factory()->create(['name' => 'Analista A']);
    $a->givePermissionTo('dashboard.ver_solo_propio');

    $b = User::factory()->create(['name' => 'Analista B']);

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $a->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'COMPLETADO',
        'intervenido' => true,
        'fecha_intervencion' => now(),
    ]);

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $a->id,
        'modulo_intervencion' => 'soplado',
        'estado_actual' => 'POR_SOPLAR',
        'intervenido' => false,
        'fecha_intervencion' => now(),
    ]);

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $b->id,
        'modulo_intervencion' => 'portatiles',
        'estado_actual' => 'INTERVENIDO',
        'intervenido' => true,
        'fecha_intervencion' => now(),
    ]);

    Sanctum::actingAs($a);

    $respuesta = $this->getJson('/api/dashboard?periodo=anio')->assertOk();

    expect($respuesta->json('data.solo_propio'))->toBeTrue();
    expect($respuesta->json('data.kpis.total_inventario'))->toBe(2);
    expect($respuesta->json('data.kpis.cerrados'))->toBe(1);
    expect($respuesta->json('data.kpis.en_transito'))->toBe(1);

    $analistas = collect($respuesta->json('data.productividad.por_analista'))->pluck('analista_id')->all();
    expect($analistas)->toBe([$a->id]);
});

it('filtra las intervenciones por rango temporal', function () {
    $admin = User::factory()->create(['name' => 'Administrador']);
    $admin->assignRole('admin');
    $admin->givePermissionTo('dashboard.ver_solo_propio');

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $admin->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'COMPLETADO',
        'intervenido' => true,
        'fecha_intervencion' => now(),
    ]);

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $admin->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'COMPLETADO',
        'intervenido' => true,
        'fecha_intervencion' => now()->subDays(10),
    ]);

    Sanctum::actingAs($admin);

    expect($this->getJson('/api/dashboard?periodo=hoy')->json('data.kpis.intervenciones_periodo'))->toBe(1);
    // `periodo=anio` captura ambos registros con independencia del día del mes.
    expect($this->getJson('/api/dashboard?periodo=anio')->json('data.kpis.intervenciones_periodo'))->toBe(2);
});

it('prioriza el rango explícito de fechas sobre el periodo', function () {
    $admin = User::factory()->create(['name' => 'Administrador']);
    $admin->assignRole('admin');
    $admin->givePermissionTo('dashboard.ver_solo_propio');

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $admin->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'COMPLETADO',
        'intervenido' => true,
        'fecha_intervencion' => now(),
    ]);

    InventarioGeneral::factory()->create([
        'analista_intervencion' => $admin->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'COMPLETADO',
        'intervenido' => true,
        'fecha_intervencion' => now()->subDays(10),
    ]);

    Sanctum::actingAs($admin);

    $hoy = now()->toDateString();
    $hace10 = now()->subDays(10)->toDateString();

    // `periodo=anio` cubriría ambas intervenciones; el rango explícito de hoy lo restringe a 1.
    expect(
        $this->getJson("/api/dashboard?periodo=anio&fecha_inicio={$hoy}&fecha_fin={$hoy}")
            ->assertOk()
            ->json('data.kpis.intervenciones_periodo'),
    )->toBe(1);

    expect(
        $this->getJson("/api/dashboard?periodo=anio&fecha_inicio={$hace10}&fecha_fin={$hoy}")
            ->assertOk()
            ->json('data.kpis.intervenciones_periodo'),
    )->toBe(2);
});

it('exporta una bitácora CSV con la estructura de columnas esperada', function () {
    $admin = User::factory()->create(['name' => 'Administrador']);
    $admin->assignRole('admin');
    $admin->givePermissionTo('dashboard.ver_solo_propio');

    $inventario = InventarioGeneral::factory()->create([
        'placa_id' => 'AF-DASH-CSV',
        'num_traslado' => 'TR-777',
        'referencia' => 'ThinkPad T14',
        'modulo_intervencion' => 'portatiles',
        'estado_actual' => 'INTERVENIDO',
        'ubicacion_actual' => 'ALMACEN',
        'analista_intervencion' => $admin->id,
        'fecha_intervencion' => now(),
        'intervenido' => true,
    ]);

    GarantiaPortatil::create([
        'inventario_general_id' => $inventario->id,
        'placa_id' => 'AF-DASH-CSV',
        'serial' => $inventario->serial,
        'marca' => $inventario->marca,
        'referencia' => $inventario->referencia,
        'num_traslado' => 'TR-777',
        'clasificacion' => 'GARANTIA',
        'test_lenovo' => true,
        'serial_disco' => 'DISCO-1',
        'ubicacion_destino' => 'ALMACEN',
        'estado' => 'INTERVENIDO',
        'analista_id' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $respuesta = $this->get('/api/dashboard/exportar-csv?periodo=anio')->assertOk();
    $contenido = $respuesta->streamedContent();

    expect($contenido)->toStartWith("\xEF\xBB\xBF");
    expect($contenido)->toContain('ID/Radicado');
    expect($contenido)->toContain('Num Traslado');
    expect($contenido)->toContain('Placa');
    expect($contenido)->toContain('Serial');
    expect($contenido)->toContain('Modulo Atendido');
    expect($contenido)->toContain('Referencia');
    expect($contenido)->toContain('Tipo Gestion / Clasificacion');
    expect($contenido)->toContain('Ubicacion Destino');
    expect($contenido)->toContain('Estado Actual');
    expect($contenido)->toContain('Analista');
    expect($contenido)->toContain('Fecha Intervencion');
    expect($contenido)->toContain('AF-DASH-CSV');
    expect($contenido)->toContain('TR-777');
    expect($contenido)->toContain('Garantía');
});

it('calcula el SLA de 4 días (fecha_cargue -> fecha_intervencion)', function () {
    $admin = User::factory()->create(['name' => 'Administrador']);
    $admin->assignRole('admin');
    $admin->givePermissionTo('dashboard.ver_solo_propio');

    $a = User::factory()->create(['name' => 'Analista A']);

    // Dentro del SLA: cargue hoy, intervención hoy (0 días).
    InventarioGeneral::factory()->create([
        'analista_intervencion' => $a->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'COMPLETADO',
        'intervenido' => true,
        'fecha_cargue' => now(),
        'fecha_intervencion' => now(),
    ]);

    // Fuera del SLA: cargue hace 10 días, intervención hoy (10 días).
    InventarioGeneral::factory()->create([
        'analista_intervencion' => $a->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'COMPLETADO',
        'intervenido' => true,
        'fecha_cargue' => now()->subDays(10),
        'fecha_intervencion' => now(),
    ]);

    Sanctum::actingAs($admin);

    $respuesta = $this->getJson('/api/dashboard?periodo=hoy')->assertOk();

    expect($respuesta->json('data.sla_dias'))->toBe(4);
    expect($respuesta->json('data.productividad.global.sla.total'))->toBe(2);
    expect($respuesta->json('data.productividad.global.sla.dentro'))->toBe(1);
    expect($respuesta->json('data.productividad.global.sla.porcentaje'))->toEqual(50.0);

    $analista = collect($respuesta->json('data.productividad.por_analista'))
        ->firstWhere('analista_id', $a->id);

    expect($analista['sla'])->toMatchArray(['dentro' => 1, 'total' => 2]);
});

it('otorga el bono máximo (15%) cuando la productividad supera el 87%', function () {
    $admin = User::factory()->create(['name' => 'Administrador']);
    $admin->assignRole('admin');
    $admin->givePermissionTo('dashboard.ver_solo_propio');

    $a = User::factory()->create(['name' => 'Analista A']);

    // 9 intervenciones en el día: 9/10 = 90% -> bono 15%.
    for ($i = 0; $i < 9; $i++) {
        InventarioGeneral::factory()->create([
            'analista_intervencion' => $a->id,
            'modulo_intervencion' => 'equipos',
            'estado_actual' => 'COMPLETADO',
            'intervenido' => true,
            'fecha_cargue' => now(),
            'fecha_intervencion' => now(),
        ]);
    }

    Sanctum::actingAs($admin);

    $respuesta = $this->getJson('/api/dashboard?periodo=hoy')->assertOk();

    expect($respuesta->json('data.productividad.global.porcentaje'))->toEqual(90.0);
    expect($respuesta->json('data.productividad.global.bono'))->toBe(15);

    $analista = collect($respuesta->json('data.productividad.por_analista'))
        ->firstWhere('analista_id', $a->id);

    expect($analista['porcentaje'])->toEqual(90.0);
    expect($analista['bono'])->toBe(15);
});

it('filtra las métricas por tipo de equipo (CPU / PORTATIL)', function () {
    $admin = User::factory()->create(['name' => 'Administrador']);
    $admin->assignRole('admin');
    $admin->givePermissionTo('dashboard.ver_solo_propio');

    InventarioGeneral::factory()->create([
        'descripcion' => 'CPU',
        'analista_intervencion' => $admin->id,
        'modulo_intervencion' => 'equipos',
        'estado_actual' => 'COMPLETADO',
        'intervenido' => true,
        'fecha_intervencion' => now(),
    ]);

    InventarioGeneral::factory()->create([
        'descripcion' => 'PORTATIL',
        'analista_intervencion' => $admin->id,
        'modulo_intervencion' => 'portatiles',
        'estado_actual' => 'INTERVENIDO',
        'intervenido' => true,
        'fecha_intervencion' => now(),
    ]);

    Sanctum::actingAs($admin);

    expect($this->getJson('/api/dashboard?periodo=anio&tipo_equipo=CPU')->json('data.kpis.total_inventario'))
        ->toBe(1);
    expect($this->getJson('/api/dashboard?periodo=anio&tipo_equipo=PORTATIL')->json('data.kpis.total_inventario'))
        ->toBe(1);
    expect($this->getJson('/api/dashboard?periodo=anio&tipo_equipo=CPU')->json('data.kpis.intervenciones_periodo'))
        ->toBe(1);
});
