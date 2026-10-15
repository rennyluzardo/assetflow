<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'inventario.ver',
            'cargue_masivo.ejecutar',
            'cpus.ver_bitacora',
            'cpus.registrar',
            'soplado.ver_bitacora',
            'soplado.registrar',
            'portatiles.ver_bitacora',
            'portatiles.registrar',
            'trazabilidad.ver',
            'dashboard.ver_solo_propio',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $gestionCompleta = [
            'inventario.ver',
            'cargue_masivo.ejecutar',
            'cpus.ver_bitacora',
            'cpus.registrar',
            'soplado.ver_bitacora',
            'soplado.registrar',
            'portatiles.ver_bitacora',
            'portatiles.registrar',
            'trazabilidad.ver',
        ];

        $operacionTaller = [
            'cpus.ver_bitacora',
            'cpus.registrar',
            'soplado.ver_bitacora',
            'soplado.registrar',
            'portatiles.ver_bitacora',
            'portatiles.registrar',
            'trazabilidad.ver',
            'dashboard.ver_solo_propio',
        ];

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($gestionCompleta);

        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $supervisor->syncPermissions($gestionCompleta);

        $analista = Role::firstOrCreate(['name' => 'analista']);
        $analista->syncPermissions($operacionTaller);

        $usuarios = [
            ['name' => 'Administrador', 'email' => 'admin@assetflow.test', 'role' => 'admin'],
            ['name' => 'Supervisor de Garantías', 'email' => 'supervisor@assetflow.test', 'role' => 'supervisor'],
            ['name' => 'Analista de Garantías', 'email' => 'analista@assetflow.test', 'role' => 'analista'],
        ];

        foreach ($usuarios as $datos) {
            $usuario = User::firstOrCreate(
                ['email' => $datos['email']],
                ['name' => $datos['name'], 'password' => Hash::make('password')],
            );

            $usuario->syncRoles([$datos['role']]);
        }
    }
}
