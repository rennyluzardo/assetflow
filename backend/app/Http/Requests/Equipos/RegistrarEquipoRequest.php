<?php

namespace App\Http\Requests\Equipos;

use App\Enums\TipoGestionEnum;
use App\Enums\UbicacionDestinoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class RegistrarEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placa_id' => ['required', 'string', 'max:255', 'exists:inventario_general,placa_id'],
            'tipo_gestion' => ['required', new Enum(TipoGestionEnum::class)],
            'requiere_soplado_o_pasta' => [Rule::requiredIf($this->necesitaRespuestaSoplado()), 'boolean'],
            'esta_funcional' => [Rule::requiredIf($this->esDiagnostico()), 'boolean'],
            'tiene_garantia' => [Rule::requiredIf($this->esDiagnostico() && $this->boolean('esta_funcional') === false), 'boolean'],
            'es_obsoleta' => [Rule::requiredIf($this->esDiagnostico() && $this->boolean('esta_funcional') === true), 'boolean'],
            'hay_pieza' => [Rule::requiredIf($this->esIntervencion()), 'boolean'],
            'ubicacion_destino' => ['required', new Enum(UbicacionDestinoEnum::class)],
            'foto_equipo' => ['nullable', 'string', 'max:2048'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function tipoGestion(): TipoGestionEnum
    {
        return TipoGestionEnum::from($this->validated('tipo_gestion'));
    }

    public function destino(): UbicacionDestinoEnum
    {
        return UbicacionDestinoEnum::from($this->validated('ubicacion_destino'));
    }

    /**
     * Respuestas clínicas del árbol de decisión presentes en el request.
     */
    public function respuestas(): array
    {
        $claves = ['requiere_soplado_o_pasta', 'esta_funcional', 'tiene_garantia', 'es_obsoleta', 'hay_pieza'];

        return collect($claves)
            ->filter(fn (string $clave) => array_key_exists($clave, $this->validated()))
            ->mapWithKeys(fn (string $clave) => [$clave => (bool) $this->validated($clave)])
            ->all();
    }

    private function esDiagnostico(): bool
    {
        return $this->input('tipo_gestion') === TipoGestionEnum::DIAGNOSTICO->value;
    }

    private function esIntervencion(): bool
    {
        return $this->input('tipo_gestion') === TipoGestionEnum::INTERVENCION->value;
    }

    private function necesitaRespuestaSoplado(): bool
    {
        return match ($this->input('tipo_gestion')) {
            TipoGestionEnum::NOVEDAD->value => true,
            TipoGestionEnum::DIAGNOSTICO->value => $this->boolean('esta_funcional') === true && $this->boolean('es_obsoleta') === false,
            TipoGestionEnum::INTERVENCION->value => $this->boolean('hay_pieza') === true,
            default => false,
        };
    }
}
