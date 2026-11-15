<?php

namespace App\Http\Requests\ZonasUbicaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarUbicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * En la ruta anidada `POST /zonas/{zona}/ubicaciones` el `zona_id` lo aporta
     * la propia ruta; se inyecta antes de validar para no exigirlo en el payload.
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('zona') && ! $this->has('zona_id')) {
            $this->merge(['zona_id' => $this->route('zona')->id]);
        }
    }

    public function rules(): array
    {
        $ubicacionId = $this->route('ubicacion')?->id;

        return [
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ubicaciones', 'nombre')
                    ->where(fn ($query) => $query->where('zona_id', $this->input('zona_id')))
                    ->ignore($ubicacionId),
            ],
        ];
    }
}
