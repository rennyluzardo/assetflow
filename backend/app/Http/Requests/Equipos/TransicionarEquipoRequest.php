<?php

namespace App\Http\Requests\Equipos;

use App\Enums\UbicacionDestinoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TransicionarEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placa_id' => ['required', 'string', 'max:255'],
            'ubicacion_destino' => ['required', new Enum(UbicacionDestinoEnum::class)],
            'requiere_soplado_o_pasta' => ['sometimes', 'boolean'],
        ];
    }

    public function destino(): UbicacionDestinoEnum
    {
        return UbicacionDestinoEnum::from($this->validated('ubicacion_destino'));
    }

    public function respuestas(): array
    {
        return collect(['requiere_soplado_o_pasta'])
            ->filter(fn (string $clave) => array_key_exists($clave, $this->validated()))
            ->mapWithKeys(fn (string $clave) => [$clave => (bool) $this->validated($clave)])
            ->all();
    }
}
