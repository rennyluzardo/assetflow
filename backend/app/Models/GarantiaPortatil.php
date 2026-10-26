<?php

namespace App\Models;

use App\Enums\CausalBajaEnum;
use App\Enums\ClasificacionPortatilEnum;
use App\Enums\EstadoEquipoEnum;
use App\Enums\GravedadNovedadEnum;
use App\Enums\OrigenDanoGarantiaEnum;
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
        'clasificacion',
        'gravedad_novedad',
        'origen_dano_garantia',
        'causal_baja',
        'cantidad_danos',
        'test_lenovo',
        'serial_disco',
        'ticket_proveedor',
        'partes_fru',
        'piezas_solicitar',
        'reparado_por',
        'observaciones',
        'foto_evidencia',
        'ubicacion_destino',
        'estado',
        'analista_id',
    ];

    protected function casts(): array
    {
        return [
            'clasificacion' => ClasificacionPortatilEnum::class,
            'gravedad_novedad' => GravedadNovedadEnum::class,
            'origen_dano_garantia' => OrigenDanoGarantiaEnum::class,
            'causal_baja' => CausalBajaEnum::class,
            'cantidad_danos' => 'integer',
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
