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
            'nombre'           => 'required|string|max:100',
            'apellidoPaterno'  => 'required|string|max:100',
            'apellidoMaterno'  => 'required|string|max:100',
            'fechaNacimiento'  => 'required|date_format:Y-m-d',
            'telefono'         => 'nullable|string|max:20',
            'sexo'             => 'nullable|string|in:Masculino,Femenino,Otro',
            'matricula' => 'required|string|max:50|unique:personas,matricula,' . optional($this->route('persona'))->_id,
            
            // Carrera: sólo si viene, y siempre como array de strings
            'carrera'          => 'sometimes|array|min:1',
            'carrera.*'        => 'string|max:100',
            
            // Cuatrimestre: Sólo si viene, array de strings
            'cuatrimestre'     => 'sometimes|array|min:1',
            'cuatrimestre.*'   => 'string|max:20',
            
            // Grupo: Sólo si viene, array de strings
            'grupo'            => 'sometimes|array|min:1',
            'grupo.*'          => 'string|max:20',
        ];
    }
}
