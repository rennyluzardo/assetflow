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

        $permisos = [
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

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permisos);

        $analista = Role::firstOrCreate(['name' => 'analista']);
        $analista->syncPermissions(['inventario.ver', 'trazabilidad.ver', 'dashboard.ver_solo_propio']);

        $this->usuario('Administrador', 'admin@assetflow.local', 'admin');
        $this->usuario('Técnico CPU', 'cpu@assetflow.local', 'analista', [
            'cpus.ver_bitacora',
            'cpus.registrar',
        ]);
        $this->usuario('Técnico Soplado', 'soplado@assetflow.local', 'analista', [
            'soplado.ver_bitacora',
            'soplado.registrar',
        ]);
        $this->usuario('Técnico Portátiles', 'portatiles@assetflow.local', 'analista', [
            'portatiles.ver_bitacora',
            'portatiles.registrar',
            'inventario.ver',
            'trazabilidad.ver',
        ]);
    }

    /**
     * Crea (o reutiliza) un usuario de desarrollo y le asigna rol + permisos directos.
     *
     * @param  string[]  $permisosDirectos
     */
    private function usuario(string $name, string $email, string $role, array $permisosDirectos = []): User
    {
        $usuario = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')],
        );

        $usuario->syncRoles([$role]);
        $usuario->syncPermissions($permisosDirectos);

        return $usuario;
    }
}
