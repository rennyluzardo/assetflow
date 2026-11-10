<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class VaciarInventarioCommand extends Command
{
    protected $signature = 'inventario:vaciar';

    protected $description = 'Vacía inventario_general, traslados y bitácoras técnicas conservando usuarios y roles.';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Este comando está bloqueado en producción.');
            return self::FAILURE;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('TRUNCATE TABLE soplado_registros, equipos, garantia_portatil, inventario_general, traslados RESTART IDENTITY CASCADE;');
        } else {
            DB::transaction(function (): void {
                DB::table('soplado_registros')->delete();
                DB::table('equipos')->delete();
                DB::table('garantia_portatil')->delete();
                DB::table('inventario_general')->delete();
                DB::table('traslados')->delete();
            });
        }

        $this->info('Inventario, bitácoras y traslados vaciados correctamente (0 registros).');

        return self::SUCCESS;
    }
}