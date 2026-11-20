<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            // Zonas/ubicaciones e inventario ya no se siembran automáticamente:
            // son datos operativos. Para recrear el set base de zonas:
            //   php artisan db:seed --class=ZonaUbicacionSeeder
            // El E2E corre los fixtures que necesita desde `e2e:reset-fixtures`.
        ]);
    }
}
