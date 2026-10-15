<?php

namespace App\Models;

use App\Enums\EstadoEquipoEnum;
use App\Enums\UbicacionDestinoEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GarantiaPortatil extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'garantia_portatil';

    protected $fillable = [
        'inventario_general_id',
        'placa_id',
        'serial',
        'marca',
        'referencia',
        'num_traslado',
        'tipo_gestion',
        'test_lenovo',
        'serial_disco',
        'ticket_proveedor',
        'parte_fru',
        'piezas_solicitar',
        'ubicacion_destino',
        'estado',
        'foto_equipo',
        'observaciones',
        'analista_id',
    ];

    protected function casts(): array
    {
        return [
            'test_lenovo' => 'boolean',
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
}
