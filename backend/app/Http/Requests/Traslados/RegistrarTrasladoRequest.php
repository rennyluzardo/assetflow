<?php

namespace App\Http\Requests\Traslados;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Creación de un traslado: solo se pide el destino. El código es autogenerado
 * y el origen se "bautiza" automáticamente con el primer equipo escaneado.
 */
class RegistrarTrasladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zona_destino' => ['required', 'string', 'max:255'],
            'ubicacion_destino' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
