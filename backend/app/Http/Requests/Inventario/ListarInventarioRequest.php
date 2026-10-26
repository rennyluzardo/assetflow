<?php

namespace App\Http\Requests\Inventario;

use App\Enums\EstadoEquipoEnum;
use App\Enums\InventarioOrdenableEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $estados = array_map(fn (EstadoEquipoEnum $estado) => $estado->value, EstadoEquipoEnum::cases());
        $estados[] = 'SIN_ESTADO'; // Activos aún sin intervención (estado_actual = null).

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'search' => ['sometimes', 'string', 'max:255'],
            'num_traslado' => ['sometimes', 'string', 'max:255'],
            'estado_actual' => ['sometimes', 'string', Rule::in($estados)],
            'sort' => ['sometimes', 'string', Rule::in(InventarioOrdenableEnum::valores())],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
