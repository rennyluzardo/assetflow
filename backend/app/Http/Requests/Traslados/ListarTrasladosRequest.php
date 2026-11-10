<?php

namespace App\Http\Requests\Traslados;

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
            'estado' => ['sometimes', 'string', Rule::in(['EN_TRANSITO', 'RECIBIDO'])],
        ];
    }
}
