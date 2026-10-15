<?php

namespace App\Models;

use App\Enums\EstadoEquipoEnum;
use App\Enums\TipoGestionEnum;
use App\Enums\UbicacionDestinoEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'equipos';

    protected $fillable = [
        'inventario_general_id',
        'placa_id',
        'serial',
        'marca',
        'referencia',
        'descripcion',
        'num_traslado',
        'tipo_gestion',
        'requiere_soplado_o_pasta',
        'esta_funcional',
        'tiene_garantia',
        'es_obsoleta',
        'hay_pieza',
        'ubicacion_destino',
        'estado',
        'foto_equipo',
        'observaciones',
        'analista_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo_gestion' => TipoGestionEnum::class,
            'requiere_soplado_o_pasta' => 'boolean',
            'esta_funcional' => 'boolean',
            'tiene_garantia' => 'boolean',
            'es_obsoleta' => 'boolean',
            'hay_pieza' => 'boolean',
            'ubicacion_destino' => UbicacionDestinoEnum::class,
            'estado' => EstadoEquipoEnum::class,
        ];
    }

    public function inventarioGeneral(): BelongsTo
    {
        return $this->belongsTo(InventarioGeneral::class, 'inventario_general_id');
    }

    public function analista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analista_id');
    }

    public function sopladoRegistros(): HasMany
    {
        return $this->hasMany(SopladoRegistro::class, 'equipo_id');
    }
}
