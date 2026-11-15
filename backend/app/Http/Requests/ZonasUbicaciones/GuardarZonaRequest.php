<?php

namespace App\Http\Requests\ZonasUbicaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarZonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $zonaId = $this->route('zona')?->id;

        return [
            'sede' => ['required', 'string', 'max:255'],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('zonas', 'nombre')
                    ->where(fn ($query) => $query->where('sede', $this->input('sede')))
                    ->ignore($zonaId),
            ],
        ];
    }
}
