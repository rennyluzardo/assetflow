<?php

namespace App\Http\Requests\CargueMasivo;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ProcesarCargueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'num_traslado' => ['required', 'string', 'max:255'],
            'archivo' => [
                'required',
                'file',
                'max:10240',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $extension = strtolower($value->getClientOriginalExtension());

                    if (! in_array($extension, ['csv', 'xlsx'], true)) {
                        $fail('El archivo debe ser un CSV o XLSX.');
                    }
                },
            ],
        ];
    }
}
