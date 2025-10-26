<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TecnicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeParam = $this->route('tecnica');
        $id = $routeParam instanceof \App\Models\Tecnica ? $routeParam->getKey() : $routeParam;

        $rules = [
            'descripcion' => 'required|string',
            'dificultad'  => 'required|string|in:Bajo,Medio,Alto',
            'duracion'    => 'required|integer|min:1',
            'categoria'   => 'required|string|max:100',

            'calificaciones'               => 'sometimes|array',
            'calificaciones.*.usuario_id'  => 'required_with:calificaciones|string',
            'calificaciones.*.puntaje'     => 'required_with:calificaciones|integer|min:1|max:5',
            'calificaciones.*.comentario'  => 'nullable|string',
            'calificaciones.*.fecha'       => 'required_with:calificaciones|date_format:Y-m-d',

            'recursos'               => 'sometimes|array',
            'recursos.*.tipo'        => 'required_with:recursos|string|in:Imagen,Video,Audio,Documento',
            'recursos.*.url'         => 'required_with:recursos|url',
            'recursos.*.descripcion' => 'nullable|string',
            'recursos.*.fecha'       => 'required_with:recursos|date_format:Y-m-d',
        ];

        if ($this->isMethod('post')) {
            $rules['nombre'] = [
                'required','string','max:150',
                Rule::unique('tecnicas', 'nombre'),
            ];
        } else {
            $rules['nombre'] = [
                'required','string','max:150',
                Rule::unique('tecnicas', 'nombre')->ignore($id, '_id'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una técnica con ese nombre.',
            'calificaciones.*.usuario_id.required_with' => 'Falta el usuario en la calificación.',
            'calificaciones.*.puntaje.min' => 'El puntaje debe ser al menos 1.',
            'calificaciones.*.puntaje.max' => 'El puntaje no puede exceder 5.',
            'recursos.*.url.url' => 'La URL del recurso no es válida.',
        ];
    }
}
