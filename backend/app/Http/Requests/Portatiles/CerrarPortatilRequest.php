<?php

namespace App\Http\Requests\Portatiles;

use App\Enums\UbicacionDestinoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CerrarPortatilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ubicacion_destino' => ['required', new Enum(UbicacionDestinoEnum::class)],
            'reparado_por' => ['required', 'string', 'max:255'],
            'foto_evidencia' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function destino(): UbicacionDestinoEnum
    {
        return UbicacionDestinoEnum::from($this->validated('ubicacion_destino'));
    }
}
