<?php

namespace App\Models;

use App\Enums\TrasladoEstadoEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Traslado extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'traslados';

    protected $fillable = [
        'codigo',
        'zona_origen',
        'ubicacion_origen',
        'zona_destino',
        'ubicacion_destino',
        'estado',
        'observaciones',
        'observacion_cierre_parcial',
        'creado_por_id',
        'despachado_por_id',
        'despachado_at',
        'recibido_por_id',
        'recibido_at',
    ];

    protected function casts(): array
    {
        return [
            'estado' => TrasladoEstadoEnum::class,
            'despachado_at' => 'datetime',
            'recibido_at' => 'datetime',
            // `withCount` devuelve el agregado como string en algunos drivers
            // (p. ej. PostgreSQL bigint); se normaliza a int para el JSON.
            'equipos_total' => 'integer',
            'equipos_recibidos' => 'integer',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    public function despachador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'despachado_por_id');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por_id');
    }

    /**
     * Activos que componen este traslado (tabla pivote `traslado_equipos`).
     */
    public function equipos(): HasMany
    {
        return $this->hasMany(TrasladoEquipo::class, 'traslado_id');
    }
}
