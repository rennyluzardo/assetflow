<?php

namespace App\Http\Requests\Traslados;

use App\Enums\TrasladoEstadoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListarTrasladosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'codigo' => ['sometimes', 'string', 'max:255'],
            'zona_origen' => ['sometimes', 'string', 'max:255'],
            'ubicacion_origen' => ['sometimes', 'string', 'max:255'],
            'zona_destino' => ['sometimes', 'string', 'max:255'],
            'ubicacion_destino' => ['sometimes', 'string', 'max:255'],
            'estado' => ['sometimes', 'string', Rule::in(TrasladoEstadoEnum::valores())],
        ];
    }
}
