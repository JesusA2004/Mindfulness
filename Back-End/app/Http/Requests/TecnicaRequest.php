<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TecnicaRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'        => 'required|string|max:150|unique:tecnicas,nombre',
            'descripcion'   => 'required|string',
            'dificultad'    => 'required|string|in:Bajo,Medio,Alto',
            'duracion'      => 'required|integer|min:1',
            'categoria'     => 'required|string|max:100',
        ];
    }
}
