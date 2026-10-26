<?php

namespace Database\Seeders;

use App\Enums\CausalBajaEnum;
use App\Enums\ClasificacionPortatilEnum;
use App\Enums\EstadoEquipoEnum;
use App\Enums\ModuloIntervencionEnum;
use App\Enums\OrigenDanoGarantiaEnum;
use App\Enums\TipoGestionEnum;
use App\Enums\UbicacionDestinoEnum;
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
 * Siembra un taller operativo realista para demostración y pruebas manuales.
 *
 * - Idempotente: limpia por prefijo de traslado demo (319126/319127/319128),
 *   sin tocar las placas reservadas de la suite E2E (AF-100001..AF-100015).
 * - Incluye las 5 máquinas reales del traslado 319126 observadas en Cubit y un
 *   lote de 75 equipos (TP-200001..TP-200075).
 * - Distribuye las intervenciones entre hoy, esta semana y este mes para que el
 *   `/dashboard` muestre productividad y cuellos de botella en todos los filtros.
 */
class DemoOperativoSeeder extends Seeder
{
    private const TRASLADOS_DEMO = ['319126', '319127', '319128'];

    /** @var list<string> */
    private const REFERENCIAS_CPU = [
        'CPU LENOVO M710Q I5 8GB 500GB HDD',
        'CPU LENOVO M720Q I5 16GB 500GB SSD',
        'CPU HP PRODESK 400 I5 16GB',
    ];

    /** @var list<string> */
    private const REFERENCIAS_LAPTOP = [
        'LAPTOP LENOVO THINKPAD T14 G2',
        'LAPTOP HP ELITEBOOK 840 G8',
    ];

    private User $cpu;

    private User $soplado;

    private User $portatiles;

    private User $cargue;

    /** @var list<array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}> */
    private array $assets = [];

    private int $cursor = 0;

    public function run(): void
    {
        $this->limpiarDemo();
        $this->cargarUsuarios();

        $this->assets = $this->construirAssets();
        $this->cursor = 0;

        // Orden de consumo: primero las 58 CPUs, después las 22 laptops.
        $this->seedCpuAbiertos();
        $this->seedSopladoCerrado();
        $this->seedCpuCerrado();
        $this->seedPortatilAbiertos();
        $this->seedPortatilesCerrados();
    }

    /**
     * Elimina todo lo sembrado por este seeder (por traslado demo) sin tocar
     * las placas E2E, que viven en los traslados TR-100x.
     */
    private function limpiarDemo(): void
    {
        DB::transaction(function (): void {
            $demoIds = InventarioGeneral::withTrashed()
                ->whereIn('num_traslado', self::TRASLADOS_DEMO)
                ->pluck('id');

            if ($demoIds->isEmpty()) {
                return;
            }

            $equipoIds = Equipo::withTrashed()->whereIn('inventario_general_id', $demoIds)->pluck('id');
            SopladoRegistro::withTrashed()->whereIn('equipo_id', $equipoIds)->forceDelete();
            Equipo::withTrashed()->whereIn('inventario_general_id', $demoIds)->forceDelete();
            GarantiaPortatil::withTrashed()->whereIn('inventario_general_id', $demoIds)->forceDelete();
            InventarioGeneral::withTrashed()->whereIn('num_traslado', self::TRASLADOS_DEMO)->forceDelete();
        });
    }

    private function cargarUsuarios(): void
    {
        $this->cpu = $this->usuario('cpu@assetflow.local', 'Técnico CPU');
        $this->soplado = $this->usuario('soplado@assetflow.local', 'Técnico Soplado');
        $this->portatiles = $this->usuario('portatiles@assetflow.local', 'Técnico Portátiles');
        $this->cargue = $this->usuario('admin@assetflow.local', 'Administrador');
    }

    private function usuario(string $email, string $name): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')],
        );
    }

    /**
     * @return list<array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}>
     */
    private function construirAssets(): array
    {
        $assets = [];

        // 4 CPUs reales del traslado 319126.
        $assets[] = $this->asset('B85793', 'MJ07AT2Q', 'Lenovo', 'CPU LENOVO M710Q I5 8GB 500GB HDD', 'CPU', '319126');
        $assets[] = $this->asset('M68180', 'MJ09L633', 'Lenovo', 'CPU LENOVO M720Q I5 16GB 500GB SSD', 'CPU', '319126');
        $assets[] = $this->asset('ZFT12204', 'MJ02YV98', 'HP', 'CPU HP PRODESK 400 I5 16GB', 'CPU', '319126');
        $assets[] = $this->asset('M48020', 'MJ04EU6C', 'Lenovo', 'CPU LENOVO M710Q I5 8GB 500GB HDD', 'CPU', '319126');

        // 54 CPUs del lote TP-200001..TP-200054.
        for ($n = 1; $n <= 54; $n++) {
            $assets[] = $this->assetTp($n, 'CPU', self::REFERENCIAS_CPU[($n - 1) % count(self::REFERENCIAS_CPU)]);
        }

        // 1 laptop real.
        $assets[] = $this->asset('BT20799', 'MXL1261YPV', 'Lenovo', 'LAPTOP LENOVO THINKPAD T14 G2', 'PORTATIL', '319126');

        // 21 laptops del lote TP-200055..TP-200075.
        for ($n = 55; $n <= 75; $n++) {
            $assets[] = $this->assetTp($n, 'PORTATIL', self::REFERENCIAS_LAPTOP[($n - 55) % count(self::REFERENCIAS_LAPTOP)]);
        }

        return $assets;
    }

    /**
     * @return array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}
     */
    private function asset(string $placa, string $serial, string $marca, string $referencia, string $descripcion, string $traslado): array
    {
        return [
            'placa_id' => $placa,
            'serial' => $serial,
            'marca' => $marca,
            'referencia' => $referencia,
            'descripcion' => $descripcion,
            'num_traslado' => $traslado,
        ];
    }

    /**
     * @return array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}
     */
    private function assetTp(int $n, string $descripcion, string $referencia): array
    {
        $marca = str_contains($referencia, 'HP') ? 'HP' : 'Lenovo';

        return $this->asset(
            sprintf('TP-2000%02d', $n),
            'SN-TP-'.sprintf('%05d', $n),
            $marca,
            $referencia,
            $descripcion,
            $this->trasladoDe($n),
        );
    }

    private function trasladoDe(int $n): string
    {
        return self::TRASLADOS_DEMO[intdiv($n - 1, 25)];
    }

    /**
     * Devuelve los próximos `$n` activos del plan y avanza el cursor.
     *
     * @return list<array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}>
     */
    private function proximos(int $n): array
    {
        $lote = array_slice($this->assets, $this->cursor, $n);
        $this->cursor += $n;

        return $lote;
    }

    private function fecha(string $bucket): Carbon
    {
        return match ($bucket) {
            'semana' => now()->subDays(2),
            'mes' => now()->subDays(12),
            default => now(),
        };
    }

    /**
     * @param  array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}  $asset
     */
    private function inventario(array $asset, EstadoEquipoEnum $estado, UbicacionDestinoEnum $destino, int $analistaId, string $modulo, Carbon $fecha): InventarioGeneral
    {
        return InventarioGeneral::create([
            'placa_id' => $asset['placa_id'],
            'serial' => $asset['serial'],
            'marca' => $asset['marca'],
            'referencia' => $asset['referencia'],
            'descripcion' => $asset['descripcion'],
            'zona_actual' => 'Almacén',
            'ubicacion_actual' => $destino,
            'num_traslado' => $asset['num_traslado'],
            'estado_actual' => $estado,
            'intervenido' => $estado->esTerminal(),
            'usuario_cargue' => $this->cargue->id,
            'analista_intervencion' => $analistaId,
            'modulo_intervencion' => $modulo,
            'fecha_intervencion' => $fecha,
        ]);
    }

    /**
     * @param  array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}  $asset
     * @param  array<string, mixed>  $campos
     */
    private function crearEquipo(array $asset, TipoGestionEnum $tipoGestion, array $campos, EstadoEquipoEnum $estado, UbicacionDestinoEnum $destino, Carbon $fecha): Equipo
    {
        $inventario = $this->inventario($asset, $estado, $destino, $this->cpu->id, ModuloIntervencionEnum::EQUIPOS->value, $fecha);

        return Equipo::create([
            'inventario_general_id' => $inventario->id,
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'marca' => $inventario->marca,
            'referencia' => $inventario->referencia,
            'descripcion' => $inventario->descripcion,
            'num_traslado' => $inventario->num_traslado,
            'tipo_gestion' => $tipoGestion,
            'requiere_soplado_o_pasta' => $campos['requiere_soplado_o_pasta'] ?? false,
            'esta_funcional' => $campos['esta_funcional'] ?? null,
            'tiene_garantia' => $campos['tiene_garantia'] ?? null,
            'es_obsoleta' => $campos['es_obsoleta'] ?? null,
            'hay_pieza' => $campos['hay_pieza'] ?? null,
            'ubicacion_destino' => $destino,
            'estado' => $estado,
            'observaciones' => $campos['observaciones'] ?? null,
            'analista_id' => $this->cpu->id,
        ]);
    }

    /**
     * Cierra en soplado un equipo en POR_SOPLAR: muta a estado terminal y
     * reasigna la intervención al técnico de soplado.
     */
    private function crearSopladoCierre(Equipo $equipo, UbicacionDestinoEnum $destino, EstadoEquipoEnum $estadoFinal, Carbon $fecha): void
    {
        SopladoRegistro::create([
            'equipo_id' => $equipo->id,
            'energiza' => true,
            'da_video' => true,
            'acceso_bios' => true,
            'tipo_suciedad' => 'polvo',
            'aplica_pasta_termica' => true,
            'foto_ruta' => 'fotos/soplado/'.$equipo->placa_id.'-interior.jpg',
            'ubicacion_destino' => $destino,
            'analista_id' => $this->soplado->id,
        ]);

        $equipo->update(['estado' => $estadoFinal, 'ubicacion_destino' => $destino]);

        $equipo->inventarioGeneral()->update([
            'estado_actual' => $estadoFinal,
            'ubicacion_actual' => $destino,
            'analista_intervencion' => $this->soplado->id,
            'modulo_intervencion' => ModuloIntervencionEnum::SOPLADO->value,
            'fecha_intervencion' => $fecha,
            'intervenido' => true,
        ]);
    }

    /**
     * @param  array{placa_id: string, serial: string, marca: string, referencia: string, descripcion: string, num_traslado: string}  $asset
     * @param  array<string, mixed>  $campos
     */
    private function crearPortatil(array $asset, array $campos, EstadoEquipoEnum $estado, UbicacionDestinoEnum $destino, Carbon $fecha): void
    {
        $inventario = $this->inventario($asset, $estado, $destino, $this->portatiles->id, ModuloIntervencionEnum::PORTATILES->value, $fecha);

        GarantiaPortatil::create([
            'inventario_general_id' => $inventario->id,
            'placa_id' => $inventario->placa_id,
            'serial' => $inventario->serial,
            'marca' => $inventario->marca,
            'referencia' => $inventario->referencia,
            'num_traslado' => $inventario->num_traslado,
            'clasificacion' => $campos['clasificacion'],
            'gravedad_novedad' => $campos['gravedad_novedad'] ?? null,
            'origen_dano_garantia' => $campos['origen_dano_garantia'] ?? null,
            'causal_baja' => $campos['causal_baja'] ?? null,
            'cantidad_danos' => $campos['cantidad_danos'] ?? null,
            'test_lenovo' => true,
            'serial_disco' => 'SD-'.substr($inventario->serial, 0, 8),
            'ticket_proveedor' => $campos['ticket_proveedor'] ?? null,
            'piezas_solicitar' => $campos['piezas_solicitar'] ?? null,
            'reparado_por' => $campos['reparado_por'] ?? null,
            'observaciones' => $campos['observaciones'] ?? 'Diagnóstico portátil completado.',
            'ubicacion_destino' => $destino,
            'estado' => $estado,
            'analista_id' => $this->portatiles->id,
        ]);
    }

    /**
     * CPUs con flujo abierto (cuellos de botella): 8 POR_SOPLAR, 3
     * PENDIENTE_GARANTIA y 4 POR_REACONDICIONAR.
     */
    private function seedCpuAbiertos(): void
    {
        foreach ([['hoy', 4], ['semana', 3], ['mes', 1]] as [$bucket, $n]) {
            foreach ($this->proximos($n) as $asset) {
                $this->crearEquipo($asset, TipoGestionEnum::NOVEDAD, [
                    'requiere_soplado_o_pasta' => true,
                    'observaciones' => 'Equipo con acumulación de polvo, requiere soplado.',
                ], EstadoEquipoEnum::POR_SOPLAR, UbicacionDestinoEnum::SALA_DE_GARANTIA, $this->fecha($bucket));
            }
        }

        foreach ([['hoy', 1], ['semana', 1], ['mes', 1]] as [$bucket, $n]) {
            foreach ($this->proximos($n) as $asset) {
                $this->crearEquipo($asset, TipoGestionEnum::DIAGNOSTICO, [
                    'esta_funcional' => false,
                    'tiene_garantia' => true,
                    'observaciones' => 'Falla de fuente, en espera de respuesta del proveedor.',
                ], EstadoEquipoEnum::PENDIENTE_GARANTIA, UbicacionDestinoEnum::SALA_DE_GARANTIA, $this->fecha($bucket));
            }
        }

        foreach ([['hoy', 2], ['semana', 1], ['mes', 1]] as [$bucket, $n]) {
            foreach ($this->proximos($n) as $asset) {
                $this->crearEquipo($asset, TipoGestionEnum::INTERVENCION, [
                    'hay_pieza' => false,
                    'observaciones' => 'Requiere disco de 512GB aún no disponible en stock.',
                ], EstadoEquipoEnum::POR_REACONDICIONAR, UbicacionDestinoEnum::SALA_DE_GARANTIA, $this->fecha($bucket));
            }
        }
    }

    /**
     * 14 CPUs cerradas vía soplado (9 hoy, 3 semana, 2 mes): alterna COMPLETADO
     * (Novedad/Diagnóstico) e INTERVENIDO (Intervención).
     */
    private function seedSopladoCerrado(): void
    {
        $plan = [['hoy', 9], ['semana', 3], ['mes', 2]];
        $indice = 0;

        foreach ($plan as [$bucket, $n]) {
            foreach ($this->proximos($n) as $asset) {
                $intervencion = $indice % 2 === 1;
                $tipo = $intervencion ? TipoGestionEnum::INTERVENCION : TipoGestionEnum::NOVEDAD;
                $estadoFinal = $intervencion ? EstadoEquipoEnum::INTERVENIDO : EstadoEquipoEnum::COMPLETADO;

                $equipo = $this->crearEquipo($asset, $tipo, [
                    'requiere_soplado_o_pasta' => true,
                    'hay_pieza' => $intervencion ? true : null,
                ], EstadoEquipoEnum::POR_SOPLAR, UbicacionDestinoEnum::SALA_DE_GARANTIA, $this->fecha($bucket));

                $this->crearSopladoCierre($equipo, UbicacionDestinoEnum::ALMACEN, $estadoFinal, $this->fecha($bucket));
                $indice++;
            }
        }
    }

    /**
     * 29 CPUs cerradas por diagnóstico: 28 COMPLETADO + 1 BAJA_NO_SIRVE.
     */
    private function seedCpuCerrado(): void
    {
        foreach ([['hoy', 4], ['semana', 11], ['mes', 13]] as [$bucket, $n]) {
            foreach ($this->proximos($n) as $asset) {
                $this->crearEquipo($asset, TipoGestionEnum::DIAGNOSTICO, [
                    'esta_funcional' => true,
                    'es_obsoleta' => false,
                    'requiere_soplado_o_pasta' => false,
                ], EstadoEquipoEnum::COMPLETADO, UbicacionDestinoEnum::ALMACEN, $this->fecha($bucket));
            }
        }

        foreach ([['hoy', 1]] as [$bucket, $n]) {
            foreach ($this->proximos($n) as $asset) {
                $this->crearEquipo($asset, TipoGestionEnum::DIAGNOSTICO, [
                    'esta_funcional' => false,
                    'tiene_garantia' => false,
                    'observaciones' => 'Tarjeta madre dañada y sin garantía vigente.',
                ], EstadoEquipoEnum::BAJA_NO_SIRVE, UbicacionDestinoEnum::SALA_DE_BAJA, $this->fecha($bucket));
            }
        }
    }

    /**
     * Laptops con flujo abierto: 2 PENDIENTE_GARANTIA y 2 POR_REACONDICIONAR.
     */
    private function seedPortatilAbiertos(): void
    {
        foreach ([['hoy', 1], ['semana', 1]] as [$bucket, $n]) {
            foreach ($this->proximos($n) as $asset) {
                $this->crearPortatil($asset, [
                    'clasificacion' => ClasificacionPortatilEnum::GARANTIA,
                    'origen_dano_garantia' => OrigenDanoGarantiaEnum::FABRICA,
                    'ticket_proveedor' => 'TICKET-DEMO-'.strtoupper(substr($asset['serial'], -4)),
                ], EstadoEquipoEnum::PENDIENTE_GARANTIA, UbicacionDestinoEnum::SALA_DE_GARANTIA, $this->fecha($bucket));
            }
        }

        foreach ([['hoy', 1], ['mes', 1]] as [$bucket, $n]) {
            foreach ($this->proximos($n) as $asset) {
                $this->crearPortatil($asset, [
                    'clasificacion' => ClasificacionPortatilEnum::DANO_REPARABLE,
                    'cantidad_danos' => 2,
                    'piezas_solicitar' => 'Pantalla 14" + teclado',
                ], EstadoEquipoEnum::POR_REACONDICIONAR, UbicacionDestinoEnum::SALA_DE_GARANTIA, $this->fecha($bucket));
            }
        }
    }

    /**
     * 18 laptops cerradas: alterna FUNCIONAL (COMPLETADO), cierre (INTERVENIDO)
     * y BAJA (BAJA_NO_SIRVE).
     */
    private function seedPortatilesCerrados(): void
    {
        $plan = [['hoy', 6], ['semana', 6], ['mes', 6]];
        $indice = 0;

        foreach ($plan as [$bucket, $n]) {
            foreach ($this->proximos($n) as $asset) {
                $caso = $indice % 3;

                if ($caso === 0) {
                    $this->crearPortatil($asset, [
                        'clasificacion' => ClasificacionPortatilEnum::FUNCIONAL,
                    ], EstadoEquipoEnum::COMPLETADO, UbicacionDestinoEnum::ALMACEN, $this->fecha($bucket));
                } elseif ($caso === 1) {
                    $this->crearPortatil($asset, [
                        'clasificacion' => ClasificacionPortatilEnum::GARANTIA,
                        'origen_dano_garantia' => OrigenDanoGarantiaEnum::FABRICA,
                        'ticket_proveedor' => 'TICKET-DEMO-'.strtoupper(substr($asset['serial'], -4)),
                        'reparado_por' => 'Proveedor Lenovo',
                    ], EstadoEquipoEnum::INTERVENIDO, UbicacionDestinoEnum::ALMACEN, $this->fecha($bucket));
                } else {
                    $this->crearPortatil($asset, [
                        'clasificacion' => ClasificacionPortatilEnum::BAJA,
                        'causal_baja' => CausalBajaEnum::OBSOLETO,
                    ], EstadoEquipoEnum::BAJA_NO_SIRVE, UbicacionDestinoEnum::SALA_DE_BAJA, $this->fecha($bucket));
                }

                $indice++;
            }
        }
    }
}
