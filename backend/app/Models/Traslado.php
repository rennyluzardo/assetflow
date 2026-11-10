<?php

namespace App\Models;

use App\Enums\SedeOperativaEnum;
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
        'ubicacion_origen_exacta',
        'zona_destino',
        'ubicacion_destino_exacta',
        'estado',
        'analista_id',
    ];

    protected function casts(): array
    {
        return [
            'zona_origen' => SedeOperativaEnum::class,
            'zona_destino' => SedeOperativaEnum::class,
            // `withCount` devuelve el agregado como string en algunos drivers
            // (p. ej. PostgreSQL bigint); se normaliza a int para el JSON.
            'equipos_count' => 'integer',
        ];
    }

    public function analista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analista_id');
    }

    /**
     * Activos que componen este traslado. El vínculo es por el código libre
     * `inventario_general.num_traslado` (no hay FK física), igual que en
     * `TrasladoService::equiposDelTraslado()`.
     */
    public function inventario(): HasMany
    {
        return $this->hasMany(InventarioGeneral::class, 'num_traslado', 'codigo');
    }
}
