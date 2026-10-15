<?php

namespace App\Models;

use App\Enums\UbicacionDestinoEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SopladoRegistro extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'soplado_registros';

    protected $fillable = [
        'equipo_id',
        'energiza',
        'da_video',
        'acceso_bios',
        'tipo_suciedad',
        'aplica_pasta_termica',
        'foto_ruta',
        'ubicacion_destino',
        'analista_id',
    ];

    protected function casts(): array
    {
        return [
            'energiza' => 'boolean',
            'da_video' => 'boolean',
            'acceso_bios' => 'boolean',
            'aplica_pasta_termica' => 'boolean',
            'ubicacion_destino' => UbicacionDestinoEnum::class,
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function analista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analista_id');
    }
}
