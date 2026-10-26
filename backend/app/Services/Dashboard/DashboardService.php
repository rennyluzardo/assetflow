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
        [$inicio, $fin, $dias] = $this->rango($periodo);

        $base = $this->baseQuery($user, $request);
        $intervenciones = $this->intervencionesQuery($user, $request, $inicio, $fin);

        return [
            'periodo' => $periodo,
            'desde' => $inicio->toIso8601String(),
            'hasta' => $fin->toIso8601String(),
            'dias' => $dias,
            'meta_diaria' => self::META_DIARIA,
            'solo_propio' => $this->esSoloPropio($user),
            'kpis' => [
                'total_inventario' => (clone $base)->count(),
                'cerrados' => (clone $base)->where('intervenido', true)->count(),
                'en_transito' => (clone $base)->whereIn('estado_actual', $this->estadosEnTransito())->count(),
                'intervenciones_periodo' => (clone $intervenciones)->count(),
            ],
            'conteo_por_estado' => $this->conteoPorEstado($base),
            'productividad' => [
                'por_analista' => $this->productividadPorAnalista($intervenciones, $dias),
                'por_estacion' => $this->productividadPorEstacion($intervenciones),
            ],
        ];
    }

    /**
     * Genera la bitácora CSV en streaming (BOM UTF-8 para Excel).
     *
     * @return \Generator<int, string>
     */
    public function exportarCsv(User $user, DashboardRequest $request): \Generator
    {
        yield "\xEF\xBB\xBF".$this->filaCsv($this->cabeceras())."\r\n";

        $periodo = $request->periodo();
        [$inicio, $fin] = $this->rango($periodo);

        $query = $this->intervencionesQuery($user, $request, $inicio, $fin)->orderBy('id');

        $placas = (clone $query)->pluck('placa_id')->unique();

        $tipoGestionPorPlaca = Equipo::whereIn('placa_id', $placas)
            ->orderBy('id')
            ->get()
            ->keyBy('placa_id')
            ->map(fn (Equipo $equipo) => $equipo->tipo_gestion?->label());

        $clasificacionPorPlaca = GarantiaPortatil::whereIn('placa_id', $placas)
            ->orderBy('id')
            ->get()
            ->keyBy('placa_id')
            ->map(fn (GarantiaPortatil $registro) => $registro->clasificacion?->label());

        $nombres = User::whereIn('id', (clone $query)->pluck('analista_intervencion')->filter())
            ->pluck('name', 'id');

        foreach ($query->cursor() as $fila) {
            yield $this->filaCsv(
                $this->columnas($fila, $tipoGestionPorPlaca, $clasificacionPorPlaca, $nombres),
            )."\r\n";
        }
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
     * @return array{0: Carbon, 1: Carbon, 2: int}
     */
    private function rango(string $periodo): array
    {
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
     * @return list<array{analista_id: int, nombre: string, total: int, meta: int}>
     */
    private function productividadPorAnalista(Builder $intervenciones, int $dias): array
    {
        $meta = self::META_DIARIA * $dias;

        return (clone $intervenciones)
            ->whereNotNull('analista_intervencion')
            ->join('users', 'users.id', '=', 'inventario_general.analista_intervencion')
            ->selectRaw('inventario_general.analista_intervencion AS analista_id, users.name AS nombre, COUNT(*) AS total')
            ->groupBy('inventario_general.analista_intervencion', 'users.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn (InventarioGeneral $fila) => [
                'analista_id' => (int) $fila->analista_id,
                'nombre' => $fila->nombre,
                'total' => (int) $fila->total,
                'meta' => $meta,
            ])
            ->all();
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
        ];
    }

    /**
     * @param  Collection<int, string|null>  $tipoGestionPorPlaca
     * @param  Collection<int, string|null>  $clasificacionPorPlaca
     * @param  Collection<int, string>  $nombres
     * @return list<string>
     */
    private function columnas(
        InventarioGeneral $fila,
        Collection $tipoGestionPorPlaca,
        Collection $clasificacionPorPlaca,
        Collection $nombres,
    ): array {
        $modulo = $fila->modulo_intervencion;

        $tipoGestion = match (true) {
            $modulo === ModuloIntervencionEnum::PORTATILES->value => $clasificacionPorPlaca->get($fila->placa_id) ?? '',
            $modulo === ModuloIntervencionEnum::EQUIPOS->value,
            $modulo === ModuloIntervencionEnum::SOPLADO->value => $tipoGestionPorPlaca->get($fila->placa_id) ?? '',
            default => '',
        };

        return [
            (string) $fila->id,
            $fila->num_traslado,
            $fila->placa_id,
            $fila->serial,
            $this->labelModulo($modulo),
            $fila->referencia ?? '',
            $tipoGestion,
            $fila->ubicacion_actual?->label() ?? '',
            $fila->estado_actual?->label() ?? '',
            $nombres->get($fila->analista_intervencion) ?? '',
            $fila->fecha_intervencion?->toDateTimeString() ?? '',
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

    /**
     * @param  list<string>  $valores
     */
    private function filaCsv(array $valores): string
    {
        return implode(',', array_map(fn (string $valor) => $this->escapeCsv($valor), $valores));
    }

    private function escapeCsv(string $valor): string
    {
        if (str_contains($valor, ',') || str_contains($valor, '"') || str_contains($valor, "\n") || str_contains($valor, "\r")) {
            return '"'.str_replace('"', '""', $valor).'"';
        }

        return $valor;
    }
}
