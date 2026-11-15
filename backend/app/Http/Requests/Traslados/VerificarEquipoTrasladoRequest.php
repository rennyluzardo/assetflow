<?php

namespace App\Http\Requests\Traslados;

use Illuminate\Foundation\Http\FormRequest;

class VerificarEquipoTrasladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placa_id' => ['required', 'string', 'max:255'],
        ];
    }
}
