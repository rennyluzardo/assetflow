<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            // Las zonas y ubicaciones ya no se siembran automáticamente con
            // `migrate:fresh --seed`: son datos operativos que se gestionan
            // desde el maestro de Zonas. Para recrear el set base manualmente:
            //   php artisan db:seed --class=ZonaUbicacionSeeder
            InventarioMockSeeder::class,
        ]);
    }
}
