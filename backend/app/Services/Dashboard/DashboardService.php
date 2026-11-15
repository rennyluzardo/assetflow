<?php

namespace App\Services\Dashboard;

use App\Enums\EstadoEquipoEnum;
use App\Enums\ModuloIntervencionEnum;
use App\Http\Requests\Dashboard\DashboardRequest;
use App\Models\Equipo;
use App\Models\GarantiaPortatil;
use App\Models\InventarioGeneral;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Control operativo del taller: KPIs, distribución por estado y productividad
 * por analista/estación, más la exportación en streaming de la bitácora.
 *
 * Regla RBAC crítica: si el usuario no es `admin`, todas las métricas se
 * filtran estrictamente por `analista_intervencion = $user->id`.
 */
final class DashboardService
{
    /** Meta diaria base: 10 intervenciones por técnico. */
    private const META_DIARIA = 10;

    /** SLA operativo: máx. 4 días entre `fecha_cargue` y `fecha_intervencion`. */
    private const SLA_DIAS = 4;

    /**
     * Escalera de bonos por cumplimiento mensual de la meta de productividad.
     *
     * @var array<int, int> Umbral mínimo de % -> % de bono.
     */
    private const BONOS = [
        87 => 15,
        85 => 10,
        83 => 5,
    ];

    /** Estados intermedios que mantienen el flujo abierto (cuellos de botella). */
    private const ESTADOS_EN_TRANSITO = [
        EstadoEquipoEnum::POR_SOPLAR,
        EstadoEquipoEnum::PENDIENTE_GARANTIA,
        EstadoEquipoEnum::POR_REACONDICIONAR,
    ];

    /**
     * @return array<string, mixed>
     */
    public function metricas(User $user, DashboardRequest $request): array
    {
        $periodo = $request->periodo();
        [$inicio, $fin, $dias] = $this->rango($periodo, $request->fechaInicio(), $request->fechaFin());

        $base = $this->baseQuery($user, $request);
        $intervenciones = $this->intervencionesQuery($user, $request, $inicio, $fin);

        return [
            'periodo' => $periodo,
            'desde' => $inicio->toIso8601String(),
            'hasta' => $fin->toIso8601String(),
            'dias' => $dias,
            'meta_diaria' => self::META_DIARIA,
            'sla_dias' => self::SLA_DIAS,
            'solo_propio' => $this->esSoloPropio($user),
            'kpis' => [
                'total_inventario' => (clone $base)->count(),
                'cerrados' => (clone $base)->where('intervenido', true)->count(),
                'en_transito' => (clone $base)->whereIn('estado_actual', $this->estadosEnTransito())->count(),
                'intervenciones_periodo' => (clone $intervenciones)->count(),
            ],
            'conteo_por_estado' => $this->conteoPorEstado($base),
            'productividad' => [
                'global' => $this->productividadGlobal($intervenciones, $dias),
                'por_analista' => $this->productividadPorAnalista($intervenciones, $dias),
                'por_estacion' => $this->productividadPorEstacion($intervenciones),
            ],
        ];
    }

    /**
     * Resuelve la bitácora completa (cabeceras + filas) respetando el RBAC de
     * "solo propio". La columna "Observaciones" se asocia al activo según la
     * estación que lo intervino.
     *
     * @return array{cabeceras: list<string>, filas: list<list<string>>}
     */
    public function bitacora(User $user, DashboardRequest $request): array
    {
        $periodo = $request->periodo();
        [$inicio, $fin] = $this->rango($periodo, $request->fechaInicio(), $request->fechaFin());

        $query = $this->intervencionesQuery($user, $request, $inicio, $fin)->orderBy('id');

        $placas = (clone $query)->pluck('placa_id')->unique();

        $equiposPorPlaca = Equipo::whereIn('placa_id', $placas)
            ->orderBy('id')
            ->get()
            ->keyBy('placa_id');

        $portatilesPorPlaca = GarantiaPortatil::whereIn('placa_id', $placas)
            ->orderBy('id')
            ->get()
            ->keyBy('placa_id');

        $nombres = User::whereIn('id', (clone $query)->pluck('analista_intervencion')->filter())
            ->pluck('name', 'id');

        $filas = [];

        foreach ($query->cursor() as $fila) {
            $filas[] = $this->columnas($fila, $equiposPorPlaca, $portatilesPorPlaca, $nombres);
        }

        return [
            'cabeceras' => $this->cabeceras(),
            'filas' => $filas,
        ];
    }

    private function baseQuery(User $user, DashboardRequest $request): Builder
    {
        $query = InventarioGeneral::query();

        if ($this->esSoloPropio($user)) {
            $query->where('analista_intervencion', $user->id);
        }

        if ($estado = $request->validated('estado')) {
            $query->where('estado_actual', $estado);
        }

        if ($tipo = $request->validated('tipo_equipo')) {
            $query->whereRaw('LOWER(descripcion) = ?', [strtolower($tipo)]);
        }

        return $query;
    }

    private function intervencionesQuery(
        User $user,
        DashboardRequest $request,
        Carbon $inicio,
        Carbon $fin,
    ): Builder {
        $query = $this->baseQuery($user, $request);

        $query->whereNotNull('fecha_intervencion');
        $query->whereBetween('fecha_intervencion', [$inicio, $fin]);

        if ($modulo = $request->validated('modulo')) {
            $query->where('modulo_intervencion', strtolower($modulo));
        }

        return $query;
    }

    private function esSoloPropio(User $user): bool
    {
        return ! $user->hasRole('admin');
    }

    /**
     * Rango temporal de las intervenciones. Cuando llegan `fecha_inicio` y
     * `fecha_fin`, tienen prioridad sobre el selector rápido de `periodo` y
     * acotan el filtro entre `inicio 00:00:00` y `fin 23:59:59`.
     *
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    private function rango(string $periodo, ?Carbon $inicioExplicito, ?Carbon $finExplicito): array
    {
        if ($inicioExplicito && $finExplicito) {
            $inicio = $inicioExplicito->copy()->startOfDay();
            $fin = $finExplicito->copy()->endOfDay();
            $dias = max(1, (int) $inicio->diffInDays($fin->copy()->startOfDay()) + 1);

            return [$inicio, $fin, $dias];
        }

        $fin = now();

        $inicio = match ($periodo) {
            'semana' => now()->startOfWeek(),
            'mes' => now()->startOfMonth(),
            'anio' => now()->startOfYear(),
            default => now()->startOfDay(),
        };

        $dias = match ($periodo) {
            'semana' => 7,
            'mes' => now()->daysInMonth,
            'anio' => now()->isLeapYear() ? 366 : 365,
            default => 1,
        };

        return [$inicio, $fin, $dias];
    }

    /**
     * @return list<array{estado: string, total: int}>
     */
    private function conteoPorEstado(Builder $base): array
    {
        return (clone $base)
            ->whereNotNull('estado_actual')
            ->selectRaw('estado_actual, COUNT(*) AS total')
            ->groupBy('estado_actual')
            ->get()
            ->map(fn (InventarioGeneral $fila) => [
                'estado' => $this->valorEstado($fila->estado_actual),
                'total' => (int) $fila->total,
            ])
            ->values()
            ->all();
    }

    /**
     * Productividad agregada de todo el periodo (todos los analistas juntos).
     *
     * @return array{total: int, meta: int, porcentaje: float, bono: int, sla: array{dentro: int, total: int, porcentaje: float}}
     */
    private function productividadGlobal(Builder $intervenciones, int $dias): array
    {
        $total = (clone $intervenciones)->count();
        $meta = self::META_DIARIA * $dias;
        $porcentaje = $meta > 0 ? round($total / $meta * 100, 2) : 0.0;

        return [
            'total' => $total,
            'meta' => $meta,
            'porcentaje' => $porcentaje,
            'bono' => $this->bonoPorcentaje($porcentaje),
            'sla' => $this->cumplimientoSla($intervenciones),
        ];
    }

    /**
     * @return list<array{analista_id: int, nombre: string, total: int, meta: int, porcentaje: float, bono: int, sla: array{dentro: int, total: int, porcentaje: float}}>
     */
    private function productividadPorAnalista(Builder $intervenciones, int $dias): array
    {
        $meta = self::META_DIARIA * $dias;
        $slaPorAnalista = $this->slaPorAnalista($intervenciones);

        return (clone $intervenciones)
            ->whereNotNull('analista_intervencion')
            ->join('users', 'users.id', '=', 'inventario_general.analista_intervencion')
            ->selectRaw('inventario_general.analista_intervencion AS analista_id, users.name AS nombre, COUNT(*) AS total')
            ->groupBy('inventario_general.analista_intervencion', 'users.name')
            ->orderByDesc('total')
            ->get()
            ->map(function (InventarioGeneral $fila) use ($meta, $slaPorAnalista): array {
                $analistaId = (int) $fila->analista_id;
                $total = (int) $fila->total;
                $porcentaje = $meta > 0 ? round($total / $meta * 100, 2) : 0.0;
                $sla = $slaPorAnalista[$analistaId] ?? ['dentro' => 0, 'total' => 0];

                return [
                    'analista_id' => $analistaId,
                    'nombre' => $fila->nombre,
                    'total' => $total,
                    'meta' => $meta,
                    'porcentaje' => $porcentaje,
                    'bono' => $this->bonoPorcentaje($porcentaje),
                    'sla' => [
                        'dentro' => $sla['dentro'],
                        'total' => $sla['total'],
                        'porcentaje' => $sla['total'] > 0
                            ? round($sla['dentro'] / $sla['total'] * 100, 2)
                            : 100.0,
                    ],
                ];
            })
            ->all();
    }

    /**
     * % de bono según la escalera de bonos para un % de cumplimiento dado.
     */
    private function bonoPorcentaje(float $porcentaje): int
    {
        foreach (self::BONOS as $umbral => $bono) {
            if ($porcentaje >= $umbral) {
                return $bono;
            }
        }

        return 0;
    }

    /**
     * Cumplimiento del SLA (fecha_cargue -> fecha_intervencion <= 4 días) sobre
     * el conjunto completo de intervenciones del periodo.
     *
     * @return array{dentro: int, total: int, porcentaje: float}
     */
    private function cumplimientoSla(Builder $intervenciones): array
    {
        $filas = (clone $intervenciones)->whereNotNull('fecha_cargue')
            ->get(['fecha_cargue', 'fecha_intervencion']);

        $dentro = 0;
        $total = $filas->count();

        foreach ($filas as $fila) {
            if ($this->cumpleSla($fila)) {
                $dentro++;
            }
        }

        return [
            'dentro' => $dentro,
            'total' => $total,
            'porcentaje' => $total > 0 ? round($dentro / $total * 100, 2) : 100.0,
        ];
    }

    /**
     * @return array<int, array{dentro: int, total: int}>
     */
    private function slaPorAnalista(Builder $intervenciones): array
    {
        $filas = (clone $intervenciones)
            ->whereNotNull('analista_intervencion')
            ->whereNotNull('fecha_cargue')
            ->get(['analista_intervencion', 'fecha_cargue', 'fecha_intervencion']);

        $resumen = [];

        foreach ($filas as $fila) {
            $id = (int) $fila->analista_intervencion;
            $resumen[$id] ??= ['dentro' => 0, 'total' => 0];
            $resumen[$id]['total']++;

            if ($this->cumpleSla($fila)) {
                $resumen[$id]['dentro']++;
            }
        }

        return $resumen;
    }

    private function cumpleSla(InventarioGeneral $fila): bool
    {
        $cargue = $fila->fecha_cargue;
        $intervencion = $fila->fecha_intervencion;

        if ($cargue === null || $intervencion === null || $intervencion->lt($cargue)) {
            return false;
        }

        return $cargue->diffInDays($intervencion) <= self::SLA_DIAS;
    }

    /**
     * @return list<array{estacion: string, label: string, total: int}>
     */
    private function productividadPorEstacion(Builder $intervenciones): array
    {
        return (clone $intervenciones)
            ->whereNotNull('modulo_intervencion')
            ->selectRaw('modulo_intervencion, COUNT(*) AS total')
            ->groupBy('modulo_intervencion')
            ->orderByDesc('total')
            ->get()
            ->map(fn (InventarioGeneral $fila) => [
                'estacion' => $fila->modulo_intervencion,
                'label' => $this->labelModulo($fila->modulo_intervencion),
                'total' => (int) $fila->total,
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    private function cabeceras(): array
    {
        return [
            'ID/Radicado',
            'Num Traslado',
            'Placa',
            'Serial',
            'Modulo Atendido',
            'Referencia',
            'Tipo Gestion / Clasificacion',
            'Ubicacion Destino',
            'Estado Actual',
            'Analista',
            'Fecha Intervencion',
            'Observaciones',
        ];
    }

    /**
     * @param  Collection<int, Equipo>  $equiposPorPlaca
     * @param  Collection<int, GarantiaPortatil>  $portatilesPorPlaca
     * @param  Collection<int, string>  $nombres
     * @return list<string>
     */
    private function columnas(
        InventarioGeneral $fila,
        Collection $equiposPorPlaca,
        Collection $portatilesPorPlaca,
        Collection $nombres,
    ): array {
        $modulo = $fila->modulo_intervencion;
        $placa = $fila->placa_id;

        $equipo = $equiposPorPlaca->get($placa);
        $portatil = $portatilesPorPlaca->get($placa);

        $tipoGestion = match (true) {
            $modulo === ModuloIntervencionEnum::PORTATILES->value => $portatil?->clasificacion?->label() ?? '',
            $modulo === ModuloIntervencionEnum::EQUIPOS->value,
            $modulo === ModuloIntervencionEnum::SOPLADO->value => $equipo?->tipo_gestion?->label() ?? '',
            default => '',
        };

        // El texto libre registrado en la estación que intervino el activo:
        // los portátiles viven en `garantia_portatil` y los CPUs (y el soplado
        // derivado de un CPU) en `equipos`.
        $observaciones = match (true) {
            $modulo === ModuloIntervencionEnum::PORTATILES->value => $portatil?->observaciones ?? '',
            default => $equipo?->observaciones ?? '',
        };

        return [
            (string) $fila->id,
            $fila->num_traslado,
            $fila->placa_id,
            $fila->serial,
            $this->labelModulo($modulo),
            $fila->referencia ?? '',
            $tipoGestion,
            $fila->ubicacion_actual ?? '',
            $fila->estado_actual?->label() ?? '',
            $nombres->get($fila->analista_intervencion) ?? '',
            $fila->fecha_intervencion?->toDateTimeString() ?? '',
            $observaciones,
        ];
    }

    /**
     * @return list<string>
     */
    private function estadosEnTransito(): array
    {
        return array_map(fn (EstadoEquipoEnum $estado) => $estado->value, self::ESTADOS_EN_TRANSITO);
    }

    private function valorEstado(mixed $estado): string
    {
        return $estado instanceof EstadoEquipoEnum ? $estado->value : (string) $estado;
    }

    private function labelModulo(?string $modulo): string
    {
        if ($modulo === null) {
            return '';
        }

        return ModuloIntervencionEnum::tryFrom($modulo)?->label() ?? $modulo;
    }
}
