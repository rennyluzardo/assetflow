<?php

namespace App\Models;

use App\Enums\EstadoEquipoEnum;
use App\Enums\UbicacionDestinoEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'usuario_cargue',
        'analista_intervencion',
        'modulo_intervencion',
        'fecha_intervencion',
    ];

    protected function casts(): array
    {
        return [
            'intervenido' => 'boolean',
            'ubicacion_actual' => UbicacionDestinoEnum::class,
            'estado_actual' => EstadoEquipoEnum::class,
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
}
