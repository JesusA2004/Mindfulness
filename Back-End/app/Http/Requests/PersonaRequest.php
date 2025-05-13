<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonaRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'            => 'required|string|max:100',
            'apellidoPaterno'   => 'required|string|max:100',
            'apellidoMaterno'   => 'required|string|max:100',
            'fechaNacimiento'   => 'required|date',
            'telefono'          => 'nullable|string|max:20',
            'sexo'              => 'nullable|string|in:Masculino,Femenino,Otro',
            'carrera'           => 'nullable|array',
            'carrera.*'         => 'string|max:100',
            'matricula'         => 'required|string|max:50|unique:personas,matricula',
            'cuatrimestre'      => 'nullable|array',
            'cuatrimestre.*'    => 'string|max:20',
            'grupo'             => 'nullable|array',
            'grupo.*'           => 'string|max:20',
        ];
    }
}
