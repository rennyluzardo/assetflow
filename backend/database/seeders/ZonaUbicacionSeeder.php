<?php

namespace Database\Seeders;

use App\Models\Ubicacion;
use App\Models\Zona;
use Illuminate\Database\Seeder;

/**
 * Siembra el maestro operativo de Zonas y Ubicaciones para el módulo de
 * traslados. Idempotente: usa `firstOrCreate` por las claves únicas
 * (sede+nombre en zonas, zona_id+nombre en ubicaciones).
 */
class ZonaUbicacionSeeder extends Seeder
{
    public function run(): void
    {
        $definiciones = [
            [
                'sede' => 'COLOMBIA',
                'nombre' => 'BOGOTA',
                'ubicaciones' => [
                    'SALA_DE_GARANTIA',
                    'ALMACEN',
                    'DBAN',
                    'SALA_DE_BAJA',
                ],
            ],
            [
                'sede' => 'COLOMBIA',
                'nombre' => 'MEDELLIN',
                'ubicaciones' => [
                    'ALMACEN',
                    'SALA_DE_BAJA',
                ],
            ],
            [
                'sede' => 'GUYANA',
                'nombre' => 'GEORGETOWN',
                'ubicaciones' => [
                    'ALMACEN',
                    'DBAN',
                ],
            ],
        ];

        foreach ($definiciones as $definicion) {
            $zona = Zona::firstOrCreate(
                ['sede' => $definicion['sede'], 'nombre' => $definicion['nombre']],
                ['activo' => true],
            );

            foreach ($definicion['ubicaciones'] as $nombre) {
                Ubicacion::firstOrCreate(
                    ['zona_id' => $zona->id, 'nombre' => $nombre],
                    ['activo' => true],
                );
            }
        }
    }
}
