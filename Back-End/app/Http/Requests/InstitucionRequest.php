<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstitucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Soporta tanto route-model binding como id string
        $routeInst = $this->route('institucion');
        $instId = is_object($routeInst) && method_exists($routeInst, 'getKey')
            ? $routeInst->getKey()
            : $routeInst;

        return [
            'nombre'      => [
                'required', 'string', 'max:255',
                Rule::unique('institucions', 'nombre')->ignore($instId, '_id'),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'direccion'   => ['nullable', 'string', 'max:500'],
            'telefono'    => [
                'nullable', 'string', 'max:30',
                'regex:/^[0-9+\-\s()]{6,30}$/', // Permite +, -, espacios, ()
            ],
            'email'       => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'     => 'El nombre de la institución es obligatorio.',
            'nombre.string'       => 'El nombre debe ser una cadena de texto.',
            'nombre.max'          => 'El nombre no debe exceder los 255 caracteres.',
            'nombre.unique'       => 'Ya existe una institución con este nombre.',

            'descripcion.string'  => 'La descripción debe ser una cadena de texto.',
            'descripcion.max'     => 'La descripción no debe exceder 1000 caracteres.',

            'direccion.string'    => 'La dirección debe ser una cadena de texto.',
            'direccion.max'       => 'La dirección no debe exceder 500 caracteres.',

            'telefono.string'     => 'El teléfono debe ser una cadena de texto.',
            'telefono.max'        => 'El teléfono no debe exceder 30 caracteres.',
            'telefono.regex'      => 'El teléfono contiene un formato no válido.',

            'email.email'         => 'El correo electrónico no es válido.',
            'email.max'           => 'El correo electrónico no debe exceder los 255 caracteres.',
        ];
    }
}
