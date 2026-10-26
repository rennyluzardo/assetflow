<?php

namespace App\Http\Requests\Portatiles;

use Illuminate\Foundation\Http\FormRequest;

class FotoPortatilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'foto_evidencia' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }
}
