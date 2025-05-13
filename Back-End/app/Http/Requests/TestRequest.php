<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'             => 'required|string|max:150|unique:tests,nombre',
            'descripcion'        => 'nullable|string',
            'duracion_estimada'  => 'required|integer|min:1',
            'fechaAplicacion'    => 'nullable|date',
            'cuestionario'       => 'nullable|array',
            'cuestionario.*.pregunta'    => 'required_with:cuestionario|string',
            'cuestionario.*.respuestas'  => 'required_with:cuestionario|array',
            'cuestionario.*.idUsuario'   => 'required_with:cuestionario|exists:users,_id',
        ];
    }
}
