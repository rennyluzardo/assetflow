<?php

namespace App\Http\Requests\Traslados;

use App\Enums\SedeOperativaEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegistrarTrasladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:255', 'unique:traslados,codigo'],
            'zona_origen' => ['required', new Enum(SedeOperativaEnum::class)],
            'ubicacion_origen_exacta' => ['nullable', 'string', 'max:255'],
            'zona_destino' => ['required', new Enum(SedeOperativaEnum::class)],
            'ubicacion_destino_exacta' => ['nullable', 'string', 'max:255'],
        ];
    }
}
