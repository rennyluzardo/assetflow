<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\EstadoEquipoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
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
            'tipo_equipo' => ['sometimes', 'string', Rule::in(['CPU', 'PORTATIL'])],
            'estado' => ['sometimes', 'string', Rule::in($estados)],
            'fecha_inicio' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'fecha_fin' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $inicio = $this->input('fecha_inicio');
            $fin = $this->input('fecha_fin');

            if ($inicio && $fin && Carbon::parse($fin)->lt(Carbon::parse($inicio))) {
                $validator->errors()->add(
                    'fecha_fin',
                    'La fecha "hasta" debe ser mayor o igual a la fecha "desde".',
                );
            }
        });
    }

    public function periodo(): string
    {
        return $this->validated()['periodo'] ?? 'hoy';
    }

    public function fechaInicio(): ?Carbon
    {
        $valor = $this->validated('fecha_inicio');

        return $valor ? Carbon::parse($valor) : null;
    }

    public function fechaFin(): ?Carbon
    {
        $valor = $this->validated('fecha_fin');

        return $valor ? Carbon::parse($valor) : null;
    }
}
