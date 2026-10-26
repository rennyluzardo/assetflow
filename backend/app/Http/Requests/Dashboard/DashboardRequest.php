<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\EstadoEquipoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $estados = array_map(fn (EstadoEquipoEnum $estado) => $estado->value, EstadoEquipoEnum::cases());

        return [
            'periodo' => ['sometimes', 'string', Rule::in(['hoy', 'semana', 'mes', 'anio'])],
            'modulo' => ['sometimes', 'string', Rule::in(['EQUIPOS', 'SOPLADO', 'PORTATILES'])],
            'estado' => ['sometimes', 'string', Rule::in($estados)],
        ];
    }

    public function periodo(): string
    {
        return $this->validated()['periodo'] ?? 'hoy';
    }
}
