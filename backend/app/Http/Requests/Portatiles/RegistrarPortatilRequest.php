<?php

namespace App\Http\Requests\Portatiles;

use App\Enums\CausalBajaEnum;
use App\Enums\ClasificacionPortatilEnum;
use App\Enums\GravedadNovedadEnum;
use App\Enums\SubtipoIntervencionEnum;
use App\Enums\UbicacionDestinoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class RegistrarPortatilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placa_id' => ['required', 'string', 'max:255', 'exists:inventario_general,placa_id'],
            'clasificacion' => ['required', new Enum(ClasificacionPortatilEnum::class)],
            'test_lenovo' => ['required', 'boolean'],
            'serial_disco' => [Rule::requiredIf($this->esBaja()), 'string', 'max:255'],
            'gravedad_novedad' => [Rule::requiredIf($this->esNovedad()), new Enum(GravedadNovedadEnum::class)],
            'causal_baja' => [Rule::requiredIf($this->esBaja()), new Enum(CausalBajaEnum::class)],
            'cantidad_danos' => [Rule::requiredIf($this->esNovedadPronunciada()), 'integer', 'min:1', 'max:4'],
            'ticket_proveedor' => [Rule::requiredIf($this->esGarantia()), 'string', 'max:255'],
            'partes_fru' => ['nullable', 'string', 'max:255'],
            'piezas_solicitar' => [Rule::requiredIf($this->necesitaPiezasSolicitar()), 'string', 'max:5000'],
            'pieza_es_nueva' => [Rule::requiredIf($this->esIntervencion()), 'boolean'],
            'subtipo_intervencion' => [Rule::requiredIf($this->esIntervencion()), new Enum(SubtipoIntervencionEnum::class)],
            'especificacion_repotenciacion' => [Rule::requiredIf($this->esRepotenciacion()), 'string', 'max:5000'],
            'observaciones' => [Rule::requiredIf($this->esNovedad()), 'string', 'max:5000'],
            'ubicacion_destino' => ['required', new Enum(UbicacionDestinoEnum::class)],
            'foto_evidencia' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function clasificacion(): ClasificacionPortatilEnum
    {
        return ClasificacionPortatilEnum::from($this->validated('clasificacion'));
    }

    public function destino(): UbicacionDestinoEnum
    {
        return UbicacionDestinoEnum::from($this->validated('ubicacion_destino'));
    }

    private function esNovedad(): bool
    {
        return $this->input('clasificacion') === ClasificacionPortatilEnum::NOVEDAD->value;
    }

    private function esGarantia(): bool
    {
        return $this->input('clasificacion') === ClasificacionPortatilEnum::GARANTIA->value;
    }

    private function esBaja(): bool
    {
        return $this->input('clasificacion') === ClasificacionPortatilEnum::BAJA->value;
    }

    private function esIntervencion(): bool
    {
        return $this->input('clasificacion') === ClasificacionPortatilEnum::INTERVENCION->value;
    }

    private function esNovedadPronunciada(): bool
    {
        return $this->esNovedad()
            && $this->input('gravedad_novedad') === GravedadNovedadEnum::PRONUNCIADA->value;
    }

    private function esRepotenciacion(): bool
    {
        return $this->esIntervencion()
            && $this->input('subtipo_intervencion') === SubtipoIntervencionEnum::REPOTENCIACION->value;
    }

    private function necesitaPiezasSolicitar(): bool
    {
        return $this->esNovedadPronunciada() || $this->esIntervencion() || $this->esGarantia();
    }
}
