<?php

namespace App\Http\Requests\Portatiles;

use App\Enums\ClasificacionPortatilEnum;
use App\Enums\EstadoEquipoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarPortatilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clasificaciones = array_map(
            fn (ClasificacionPortatilEnum $c) => $c->value,
            ClasificacionPortatilEnum::cases(),
        );
        $estados = array_map(
            fn (EstadoEquipoEnum $e) => $e->value,
            EstadoEquipoEnum::cases(),
        );

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'placa_id' => ['sometimes', 'string', 'max:255'],
            'num_traslado' => ['sometimes', 'string', 'max:255'],
            'clasificacion' => ['sometimes', 'string', Rule::in($clasificaciones)],
            'estado' => ['sometimes', 'string', Rule::in($estados)],
        ];
    }
}
