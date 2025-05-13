<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecompensaRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'              => 'required|string|max:150',
            'descripcion'         => 'nullable|string',
            'puntos_necesarios'   => 'required|integer|min:0',
            'stock'               => 'nullable|integer|min:0',
        ];
    }
}
