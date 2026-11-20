<?php

namespace Database\Seeders;

use App\Enums\UbicacionDestinoEnum;
use App\Models\InventarioGeneral;
use App\Models\User;
use App\Support\HistoriaTrazabilidadE2eFixture;
use App\Support\InventarioE2eFixtures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Siembra manual las placas reservadas para la suite E2E de Playwright.
 *
 * Ya NO forma parte de `migrate:fresh --seed`; `e2e:reset-fixtures` es quien
 * garantiza estos fixtures (incluida la hoja de vida AF-100016) antes de cada
 * corrida. Este seeder queda disponible para sembrarlos a mano:
 *   php artisan db:seed --class=InventarioMockSeeder
 */
class InventarioMockSeeder extends Seeder
{
    private User $cargue;

    public function run(): void
    {
        if (InventarioGeneral::where('placa_id', 'AF-100001')->exists()) {
            return;
        }

        $this->cargue = $this->usuario('admin@assetflow.local', 'Administrador');

        $this->seedPendientes();
        HistoriaTrazabilidadE2eFixture::asegurar();
    }

    private function usuario(string $email, string $name): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')],
        );
    }

    /**
     * Crea (o reutiliza) una fila virgen de inventario.
     *
     * @param  array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}  $definicion
     */
    private function inventario(array $definicion): InventarioGeneral
    {
        return InventarioGeneral::create($definicion + [
            'zona_actual' => 'Almacén',
            'ubicacion_actual' => UbicacionDestinoEnum::ALMACEN,
            'estado_actual' => null,
            'intervenido' => false,
            'usuario_cargue' => $this->cargue->id,
        ]);
    }

    private function seedPendientes(): void
    {
        foreach (InventarioE2eFixtures::definiciones() as $definicion) {
            $this->inventario($definicion);
        }
    }
}
