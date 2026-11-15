<?php

namespace App\Http\Requests\Traslados;

use Illuminate\Foundation\Http\FormRequest;

class RecibirTrasladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
