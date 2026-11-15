<?php

namespace App\Http\Requests\Traslados;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ImportarExcelTrasladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
