<?php

namespace Database\Seeders;

use App\Models\Equipo;
use App\Models\GarantiaPortatil;
use App\Models\InventarioGeneral;
use App\Models\SopladoRegistro;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Rellena el inventario con activos "sin estado" (vírgenes) para que QA pueda
 * ejercitar los flujos del taller a escala: 500 CPUs y 500 portátiles.
 *
 * - Idempotente: limpia su propio bloque por traslado (`QA-CPU` / `QA-LAP`) y lo
 *   vuelve a sembrar, sin tocar las placas reservadas E2E (AF-*) ni los datos de
 *   demostración (traslados 319126/319127/319128).
 * - Cada activo queda `estado_actual = null` e `intervenido = false`, listo para
 *   ser escaneado en /equipos o /portatiles.
 */
class QaSinEstadoSeeder extends Seeder
{
    private const CPU_TOTAL = 500;

    private const PORTATIL_TOTAL = 500;

    private const TRASLADO_CPU = 'QA-CPU';

    private const TRASLADO_LAPTOP = 'QA-LAP';

    /** @var list<string> */
    private const REFERENCIAS_CPU = [
        'CPU LENOVO M710Q I5 8GB 500GB HDD',
        'CPU LENOVO M720Q I5 16GB 500GB SSD',
        'CPU HP PRODESK 400 I5 16GB',
        'CPU DELL OPTIPLEX 7010 I5 8GB 500GB',
    ];

    /** @var list<string> */
    private const REFERENCIAS_LAPTOP = [
        'LAPTOP LENOVO THINKPAD T14 G2',
        'LAPTOP HP ELITEBOOK 840 G8',
    ];

    public function run(): void
    {
        $this->limpiar();

        $cargue = $this->usuarioCargue();
        $ahora = now();

        $filas = [
            ...$this->definirCpus($cargue->id, $ahora),
            ...$this->definirPortatiles($cargue->id, $ahora),
        ];

        foreach (array_chunk($filas, 500) as $lote) {
            DB::table('inventario_general')->insert($lote);
        }

        $this->command?->info(sprintf(
            'Inventario QA "sin estado": %d CPUs + %d portátiles.',
            self::CPU_TOTAL,
            self::PORTATIL_TOTAL,
        ));
    }

    /**
     * Elimina lo sembrado previamente por este seeder (por traslado QA) junto
     * con cualquier hijo que un tester haya podido crear en pruebas.
     */
    private function limpiar(): void
    {
        $traslados = [self::TRASLADO_CPU, self::TRASLADO_LAPTOP];

        DB::transaction(function () use ($traslados): void {
            $ids = InventarioGeneral::withTrashed()->whereIn('num_traslado', $traslados)->pluck('id');

            if ($ids->isEmpty()) {
                return;
            }

            $equipoIds = Equipo::withTrashed()->whereIn('inventario_general_id', $ids)->pluck('id');
            SopladoRegistro::withTrashed()->whereIn('equipo_id', $equipoIds)->forceDelete();
            Equipo::withTrashed()->whereIn('inventario_general_id', $ids)->forceDelete();
            GarantiaPortatil::withTrashed()->whereIn('inventario_general_id', $ids)->forceDelete();
            InventarioGeneral::withTrashed()->whereIn('num_traslado', $traslados)->forceDelete();
        });
    }

    private function usuarioCargue(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@assetflow.local'],
            ['name' => 'Administrador', 'password' => Hash::make('password')],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definirCpus(int $cargueId, Carbon $ahora): array
    {
        $filas = [];

        for ($n = 1; $n <= self::CPU_TOTAL; $n++) {
            $referencia = self::REFERENCIAS_CPU[($n - 1) % count(self::REFERENCIAS_CPU)];
            $filas[] = $this->filaSinEstado(
                sprintf('QA-CPU-%06d', $n),
                sprintf('SN-QA-CPU-%06d', $n),
                $this->marcaDe($referencia),
                $referencia,
                'CPU',
                self::TRASLADO_CPU,
                $cargueId,
                $ahora,
            );
        }

        return $filas;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definirPortatiles(int $cargueId, Carbon $ahora): array
    {
        $filas = [];

        for ($n = 1; $n <= self::PORTATIL_TOTAL; $n++) {
            $referencia = self::REFERENCIAS_LAPTOP[($n - 1) % count(self::REFERENCIAS_LAPTOP)];
            $filas[] = $this->filaSinEstado(
                sprintf('QA-LAP-%06d', $n),
                sprintf('SN-QA-LAP-%06d', $n),
                $this->marcaDe($referencia),
                $referencia,
                'PORTATIL',
                self::TRASLADO_LAPTOP,
                $cargueId,
                $ahora,
            );
        }

        return $filas;
    }

    private function marcaDe(string $referencia): string
    {
        if (str_contains($referencia, 'HP')) {
            return 'HP';
        }

        if (str_contains($referencia, 'DELL')) {
            return 'Dell';
        }

        return 'Lenovo';
    }

    /**
     * @return array<string, mixed>
     */
    private function filaSinEstado(string $placa, string $serial, string $marca, string $referencia, string $descripcion, string $traslado, int $cargueId, Carbon $ahora): array
    {
        return [
            'placa_id' => $placa,
            'serial' => $serial,
            'marca' => $marca,
            'referencia' => $referencia,
            'descripcion' => $descripcion,
            'zona_actual' => 'Almacén',
            'ubicacion_actual' => 'ALMACEN',
            'num_traslado' => $traslado,
            'estado_actual' => null,
            'intervenido' => false,
            'usuario_cargue' => $cargueId,
            'analista_intervencion' => null,
            'modulo_intervencion' => null,
            'fecha_intervencion' => null,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];
    }
}
