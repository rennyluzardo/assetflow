<?php

namespace App\Models;

use App\Enums\EstadoEquipoEnum;
use App\Enums\TrasladoEstadoEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class InventarioGeneral extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inventario_general';

    protected $fillable = [
        'placa_id',
        'serial',
        'marca',
        'referencia',
        'descripcion',
        'zona_actual',
        'ubicacion_actual',
        'num_traslado',
        'estado_actual',
        'intervenido',
        'fecha_cargue',
        'ubicacion_origen',
        'usuario_cargue',
        'analista_intervencion',
        'modulo_intervencion',
        'fecha_intervencion',
    ];

    protected function casts(): array
    {
        return [
            'intervenido' => 'boolean',
            // `ubicacion_actual` es texto libre: los traslados pueden mover el
            // activo a cualquier ubicación del maestro de Zonas/Ubicaciones,
            // más allá de los 4 destinos de taller del enum
            // `UbicacionDestinoEnum`.
            'estado_actual' => EstadoEquipoEnum::class,
            'fecha_cargue' => 'datetime',
            'fecha_intervencion' => 'datetime',
        ];
    }

    public function usuarioCargue(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cargue');
    }

    public function analistaIntervencion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analista_intervencion');
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'inventario_general_id');
    }

    public function garantiasPortatil(): HasMany
    {
        return $this->hasMany(GarantiaPortatil::class, 'inventario_general_id');
    }

    /**
     * Historial de traslados del activo (pivote `traslado_equipos`), un activo
     * puede viajar en múltiples traslados a lo largo de su vida útil.
     */
    public function trasladoEquipos(): HasMany
    {
        return $this->hasMany(TrasladoEquipo::class, 'inventario_id');
    }

    /**
     * Alcance base de `/inventario` y de las estaciones: solo los activos que ya
     * fueron recibidos formalmente (tienen `num_traslado`) y que no viajan en un
     * traslado abierto o en tránsito.
     */
    public function scopeRecibidos(Builder $query): Builder
    {
        return $query
            ->whereNotNull('num_traslado')
            ->whereDoesntHave('trasladoEquipos', fn (Builder $sub) => $sub
                ->whereHas('traslado', fn (Builder $t) => $t->whereIn('estado', [
                    TrasladoEstadoEnum::ABIERTO->value,
                    TrasladoEstadoEnum::EN_TRANSITO->value,
                ])));
    }

    /**
     * True si el activo puede ser intervenido en `/equipos`, `/portatiles` o
     * `/soplado`: ya fue recibido formalmente y no está dentro de un traslado
     * abierto o en tránsito.
     */
    public function estaDisponibleParaIntervencion(): bool
    {
        if ($this->num_traslado === null) {
            return false;
        }

        return ! $this->trasladoEquipos()
            ->whereHas('traslado', fn (Builder $t) => $t->whereIn('estado', [
                TrasladoEstadoEnum::ABIERTO->value,
                TrasladoEstadoEnum::EN_TRANSITO->value,
            ]))
            ->exists();
    }

    /**
     * Candado de estaciones: rechaza con 422 la intervención sobre una placa
     * cuyo traslado aún no ha sido recibido (o que nunca lo fue). No hace nada
     * si la placa no existe, dejando el 404 a `firstOrFail()`.
     */
    public static function asegurarRecibido(string $placaId): void
    {
        $inventario = static::where('placa_id', $placaId)->first();

        if ($inventario !== null && ! $inventario->estaDisponibleParaIntervencion()) {
            throw ValidationException::withMessages([
                'placa_id' => 'El equipo no está disponible para intervención porque su traslado aún no ha sido recibido.',
            ]);
        }
    }
}
