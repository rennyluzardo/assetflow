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
            'esta_funcional' => [Rule::requiredIf($this->esDiagnostico() || $this->esBaja()), 'boolean'],
            'tiene_garantia' => [Rule::requiredIf($this->esDiagnostico() && $this->boolean('esta_funcional') === false), 'boolean'],
            'hay_pieza' => [Rule::requiredIf($this->esIntervencion()), 'boolean'],
            'observaciones' => [Rule::requiredIf($this->esNovedad()), 'string', 'max:5000'],
            'motivo_baja' => [Rule::requiredIf($this->necesitaMotivoBaja()), 'string', 'max:5000'],
            'serial_disco' => [Rule::requiredIf($this->necesitaSerialDisco()), 'string', 'max:255'],
            'ticket_garantia' => [Rule::requiredIf($this->esDiagnosticoGarantia()), 'string', 'max:255'],
            'pieza_garantia' => [Rule::requiredIf($this->esDiagnosticoGarantia()), 'string', 'max:255'],
            'ubicacion_destino' => ['required', new Enum(UbicacionDestinoEnum::class)],
            'foto_equipo' => [
                Rule::requiredIf(fn () => in_array($this->input('tipo_gestion'), ['NOVEDAD', 'DIAGNOSTICO', 'INTERVENCION'], true)),
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'foto_equipo.required' => 'La evidencia fotográfica del equipo es obligatoria para Novedad, Diagnóstico e Intervención.',
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
        $claves = ['requiere_soplado_o_pasta', 'esta_funcional', 'tiene_garantia', 'hay_pieza'];

        return collect($claves)
            ->filter(fn (string $clave) => array_key_exists($clave, $this->validated()))
            ->mapWithKeys(fn (string $clave) => [$clave => (bool) $this->validated($clave)])
            ->all();
    }

    private function esNovedad(): bool
    {
        return $this->input('tipo_gestion') === TipoGestionEnum::NOVEDAD->value;
    }

    private function esDiagnostico(): bool
    {
        return $this->input('tipo_gestion') === TipoGestionEnum::DIAGNOSTICO->value;
    }

    private function esIntervencion(): bool
    {
        return $this->input('tipo_gestion') === TipoGestionEnum::INTERVENCION->value;
    }

    private function esBaja(): bool
    {
        return $this->input('tipo_gestion') === TipoGestionEnum::BAJA->value;
    }

    private function esDiagnosticoNoFuncionalSinGarantia(): bool
    {
        return $this->esDiagnostico()
            && $this->boolean('esta_funcional') === false
            && $this->boolean('tiene_garantia') === false;
    }

    private function esDiagnosticoGarantia(): bool
    {
        return $this->esDiagnostico()
            && $this->boolean('esta_funcional') === false
            && $this->boolean('tiene_garantia') === true;
    }

    private function necesitaRespuestaSoplado(): bool
    {
        return match ($this->input('tipo_gestion')) {
            TipoGestionEnum::NOVEDAD->value => true,
            TipoGestionEnum::DIAGNOSTICO->value => $this->boolean('esta_funcional') === true,
            TipoGestionEnum::INTERVENCION->value => $this->boolean('hay_pieza') === true,
            default => false,
        };
    }

    private function necesitaMotivoBaja(): bool
    {
        return $this->esDiagnosticoNoFuncionalSinGarantia()
            || ($this->esBaja() && $this->boolean('esta_funcional') === false);
    }

    private function necesitaSerialDisco(): bool
    {
        return $this->esDiagnosticoNoFuncionalSinGarantia() || $this->esBaja();
    }
}
