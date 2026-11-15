<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fila pivote de `traslado_equipos`: un activo viajando en un traslado concreto.
 * Conserva el historial completo de traslados por activo a lo largo de su vida.
 */
class TrasladoEquipo extends Model
{
    use HasFactory;

    protected $table = 'traslado_equipos';

    protected $fillable = [
        'traslado_id',
        'inventario_id',
        'placa_id',
        'zona_origen_snapshot',
        'ubicacion_origen_snapshot',
        'recibido',
        'recibido_por_id',
        'recibido_at',
    ];

    protected function casts(): array
    {
        return [
            'recibido' => 'boolean',
            'recibido_at' => 'datetime',
        ];
    }

    public function traslado(): BelongsTo
    {
        return $this->belongsTo(Traslado::class, 'traslado_id');
    }

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(InventarioGeneral::class, 'inventario_id');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por_id');
    }
}
