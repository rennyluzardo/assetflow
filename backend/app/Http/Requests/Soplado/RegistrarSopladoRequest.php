<?php

namespace App\Http\Requests\Soplado;

use App\Enums\UbicacionDestinoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegistrarSopladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placa_id' => ['required', 'string', 'max:255'],
            'energiza' => ['required', 'boolean'],
            'da_video' => ['required', 'boolean'],
            'acceso_bios' => ['required', 'boolean'],
            'tipo_suciedad' => ['required', 'string', 'max:50'],
            'aplica_pasta_termica' => ['required', 'boolean'],
            'foto_ruta' => ['required', 'string', 'max:2048'],
            'ubicacion_destino' => ['required', new Enum(UbicacionDestinoEnum::class)],
        ];
    }

    public function destino(): UbicacionDestinoEnum
    {
        return UbicacionDestinoEnum::from($this->validated('ubicacion_destino'));
    }
}
