<?php

use App\Models\InventarioGeneral;
use Database\Seeders\QaSinEstadoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('siembra 500 CPUs y 500 portátiles en estado virgen', function () {
    $this->seed(QaSinEstadoSeeder::class);

    expect(InventarioGeneral::count())->toBe(1000);
    expect(InventarioGeneral::where('descripcion', 'CPU')->count())->toBe(500);
    expect(InventarioGeneral::where('descripcion', 'PORTATIL')->count())->toBe(500);

    // Todas "sin estado": sin estado_actual y sin intervención.
    expect(InventarioGeneral::whereNull('estado_actual')->count())->toBe(1000);
    expect(InventarioGeneral::where('intervenido', false)->count())->toBe(1000);
});

it('es idempotente al ejecutarse dos veces', function () {
    $this->seed(QaSinEstadoSeeder::class);
    $this->seed(QaSinEstadoSeeder::class);

    expect(InventarioGeneral::count())->toBe(1000);
    expect(InventarioGeneral::where('num_traslado', 'QA-CPU')->count())->toBe(500);
    expect(InventarioGeneral::where('num_traslado', 'QA-LAP')->count())->toBe(500);
});

it('no toca placas ajenas a su bloque QA', function () {
    InventarioGeneral::factory()->create([
        'placa_id' => 'AF-100001',
        'num_traslado' => 'TR-1001',
    ]);

    $this->seed(QaSinEstadoSeeder::class);

    expect(InventarioGeneral::where('placa_id', 'AF-100001')->exists())->toBeTrue();
    expect(InventarioGeneral::count())->toBe(1001);
});
