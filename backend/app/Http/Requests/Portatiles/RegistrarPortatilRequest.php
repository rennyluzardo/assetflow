<?php

namespace App\Http\Requests\Portatiles;

use App\Enums\CausalBajaEnum;
use App\Enums\ClasificacionPortatilEnum;
use App\Enums\GravedadNovedadEnum;
use App\Enums\OrigenDanoGarantiaEnum;
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
            'serial_disco' => ['required', 'string', 'max:255'],
            'gravedad_novedad' => [Rule::requiredIf($this->esNovedad()), new Enum(GravedadNovedadEnum::class)],
            'origen_dano_garantia' => [Rule::requiredIf($this->esGarantia()), new Enum(OrigenDanoGarantiaEnum::class)],
            'causal_baja' => [Rule::requiredIf($this->esBaja()), new Enum(CausalBajaEnum::class)],
            'cantidad_danos' => [Rule::requiredIf($this->necesitaCantidadDanos()), 'integer', 'min:1'],
            'ticket_proveedor' => [Rule::requiredIf($this->esGarantiaFabrica()), 'string', 'max:255'],
            'partes_fru' => ['nullable', 'string', 'max:255'],
            'piezas_solicitar' => [Rule::requiredIf($this->necesitaPiezasSolicitar()), 'string', 'max:5000'],
            'ubicacion_destino' => ['required', new Enum(UbicacionDestinoEnum::class)],
            'observaciones' => ['nullable', 'string', 'max:5000'],
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

    private function esDanoReparable(): bool
    {
        return $this->input('clasificacion') === ClasificacionPortatilEnum::DANO_REPARABLE->value;
    }

    private function esNovedadPronunciada(): bool
    {
        return $this->esNovedad()
            && $this->input('gravedad_novedad') === GravedadNovedadEnum::PRONUNCIADA->value;
    }

    private function esGarantiaFabrica(): bool
    {
        return $this->esGarantia()
            && $this->input('origen_dano_garantia') === OrigenDanoGarantiaEnum::FABRICA->value;
    }

    private function esGarantiaInducido(): bool
    {
        return $this->esGarantia()
            && $this->input('origen_dano_garantia') === OrigenDanoGarantiaEnum::INDUCIDO->value;
    }

    private function necesitaCantidadDanos(): bool
    {
        return $this->esDanoReparable() || $this->esNovedadPronunciada();
    }

    private function necesitaPiezasSolicitar(): bool
    {
        return $this->esDanoReparable() || $this->esNovedadPronunciada() || $this->esGarantiaInducido();
    }
}
