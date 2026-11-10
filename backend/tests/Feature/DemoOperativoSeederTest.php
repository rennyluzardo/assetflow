<?php

use App\Models\Traslado;
use Database\Seeders\DemoOperativoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('crea las cabeceras de traslado de los lotes demo con rutas realistas', function () {
    $this->seed(DemoOperativoSeeder::class);

    expect(Traslado::whereIn('codigo', ['319126', '319127', '319128'])->count())->toBe(3);

    $this->assertDatabaseHas('traslados', [
        'codigo' => '319126',
        'zona_origen' => 'BOGOTA',
        'zona_destino' => 'BOGOTA',
        'ubicacion_destino_exacta' => '30052001965-CNCTF_P01_GARANTIAS',
        'estado' => 'EN_TRANSITO',
    ]);

    $this->assertDatabaseHas('traslados', [
        'codigo' => '319127',
        'zona_origen' => 'MEDELLIN',
        'zona_destino' => 'BOGOTA',
    ]);

    $this->assertDatabaseHas('traslados', [
        'codigo' => '319128',
        'zona_origen' => 'MEDELLIN',
        'zona_destino' => 'BOGOTA',
    ]);
});

it('es idempotente al ejecutarse dos veces', function () {
    $this->seed(DemoOperativoSeeder::class);
    $this->seed(DemoOperativoSeeder::class);

    expect(Traslado::whereIn('codigo', ['319126', '319127', '319128'])->count())->toBe(3);
});
