<?php

namespace App\Console\Commands;

use App\Enums\UbicacionDestinoEnum;
use App\Models\Equipo;
use App\Models\GarantiaPortatil;
use App\Models\InventarioGeneral;
use App\Models\SopladoRegistro;
use App\Models\User;
use App\Support\InventarioE2eFixtures;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetE2eFixturesCommand extends Command
{
    protected $signature = 'e2e:reset-fixtures';

    protected $description = 'Devuelve las placas reservadas de Playwright (CPUs AF-100001..AF-100015 y portátiles AF-100101..AF-100110) a su estado virgen.';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('e2e:reset-fixtures está bloqueado en producción.');

            return self::FAILURE;
        }

        $placas = InventarioE2eFixtures::placas();

        DB::transaction(function () use ($placas): void {
            // Hijos primero: soplado_registros cuelga de equipos; ambos y
            // garantia_portatil cuelgan de inventario_general.
            $equipoIds = Equipo::withTrashed()->whereIn('placa_id', $placas)->pluck('id');
            SopladoRegistro::withTrashed()->whereIn('equipo_id', $equipoIds)->forceDelete();
            Equipo::withTrashed()->whereIn('placa_id', $placas)->forceDelete();
            GarantiaPortatil::withTrashed()->whereIn('placa_id', $placas)->forceDelete();

            $this->restaurarInventario($placas);
        });

        $this->info('Fixtures E2E restablecidos: '.implode(', ', $placas).'.');

        return self::SUCCESS;
    }

    /**
     * Restaura las filas existentes a su estado virgen o recrea las faltantes.
     *
     * @param  list<string>  $placas
     */
    private function restaurarInventario(array $placas): void
    {
        $cargue = $this->usuarioCargue();

        foreach (InventarioE2eFixtures::definiciones() as $definicion) {
            $virgen = [
                'zona_actual' => 'Almacén',
                'ubicacion_actual' => UbicacionDestinoEnum::ALMACEN,
                'estado_actual' => null,
                'intervenido' => false,
                'analista_intervencion' => null,
                'modulo_intervencion' => null,
                'fecha_intervencion' => null,
            ];

            $fila = InventarioGeneral::withTrashed()->where('placa_id', $definicion['placa_id'])->first();

            if ($fila) {
                $fila->restore();
                $fila->update($definicion + $virgen);

                continue;
            }

            InventarioGeneral::create($definicion + $virgen + [
                'usuario_cargue' => $cargue->id,
            ]);
        }
    }

    /**
     * Resuelve el usuario de cargue (admin) para recrear placas faltantes.
     */
    private function usuarioCargue(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@assetflow.local'],
            ['name' => 'Administrador', 'password' => Hash::make('password')],
        );
    }
}
