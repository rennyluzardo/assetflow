<?php

namespace App\Console\Commands;

use App\Services\Traslados\TrasladoService;
use Illuminate\Console\Command;

class SincronizarTrasladosCommand extends Command
{
    protected $signature = 'traslados:sincronizar';

    protected $description = 'Crea en `traslados` la cabecera de todo `num_traslado` de `inventario_general` que aún no la tenga.';

    public function __construct(private readonly TrasladoService $traslados)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('traslados:sincronizar está bloqueado en producción.');

            return self::FAILURE;
        }

        $resultado = $this->traslados->sincronizar();

        $this->info(sprintf(
            'Sincronización de traslados completada: %d creados, %d ya existentes.',
            count($resultado['creados']),
            $resultado['existentes'],
        ));

        foreach ($resultado['creados'] as $codigo) {
            $this->line('  + '.$codigo);
        }

        return self::SUCCESS;
    }
}
