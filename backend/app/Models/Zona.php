<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zona extends Model
{
    use HasFactory;

    protected $table = 'zonas';

    protected $fillable = [
        'sede',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class, 'zona_id');
    }
}
