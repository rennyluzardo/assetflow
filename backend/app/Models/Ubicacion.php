<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'zona_id',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }
}
