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
            // Campos principales
            'nombre'      => 'required|string|max:150|unique:tecnicas,nombre,' . $this->route('tecnica'),
            'descripcion' => 'required|string',
            'dificultad'  => 'required|string|in:Bajo,Medio,Alto',
            'duracion'    => 'required|integer|min:1',
            'categoria'   => 'required|string|max:100',

            // Campo embebido `calificaciones` (opcional en creación)
            'calificaciones'                       => 'sometimes|array',
            'calificaciones.*.usuario_id'          => 'required_with:calificaciones|exists:users,_id',
            'calificaciones.*.puntaje'             => 'required_with:calificaciones|integer|min:1|max:5',
            'calificaciones.*.comentario'          => 'nullable|string',
            'calificaciones.*.fecha'               => 'required_with:calificaciones|date_format:Y-m-d',

            // Campo embebido `recursos` (opcional en creación)
            'recursos'                             => 'sometimes|array',
            'recursos.*.tipo'                      => 'required_with:recursos|string|in:Video,Audio,Documento',
            'recursos.*.url'                       => 'required_with:recursos|url',
            'recursos.*.descripcion'               => 'nullable|string',
            'recursos.*.fecha'                     => 'required_with:recursos|date_format:Y-m-d',
        ];
    }

    public function messages(): array
    {
        return [
            'calificaciones.*.usuario_id.exists' => 'El usuario calificador no existe.',
            'calificaciones.*.puntaje.min'       => 'El puntaje debe ser al menos 1.',
            'calificaciones.*.puntaje.max'       => 'El puntaje no puede exceder de 5.',
            'recursos.*.url.url'                 => 'La URL del recurso debe ser una dirección válida.',
            // …y así sucesivamente para cada validación anidada
        ];
    }
}

