<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $routeParam = $this->route('test');
        $id = $routeParam instanceof \App\Models\Test ? $routeParam->getKey() : $routeParam;

        return [
            'nombre'            => ['required','string','max:150', Rule::unique('tests','nombre')->ignore($id, '_id')],
            'descripcion'       => 'nullable|string',
            'duracion_estimada' => 'required|integer|min:1',
            'fechaAplicacion'   => 'nullable|date_format:Y-m-d',

            // cuestionario opcional, con estructura estandarizada
            'cuestionario'                 => 'sometimes|array|min:1',
            'cuestionario.*._id'           => 'required_with:cuestionario|string|max:50',
            'cuestionario.*.pregunta'      => 'required_with:cuestionario|string|max:255',
            'cuestionario.*.tipo'          => 'required_with:cuestionario|string|in:opcion_multiple,seleccion_multiple,respuesta_abierta',
            'cuestionario.*.opciones'      => 'nullable|array',
            'cuestionario.*.opciones.*'    => 'string|max:200',

            // al crear/editar, NO exigimos respuestas; solo si vienen, validamos forma
            'cuestionario.*.respuestas_por_usuario'                        => 'nullable|array',
            'cuestionario.*.respuestas_por_usuario.*.usuario_id'           => 'required_with:cuestionario.*.respuestas_por_usuario|string',
            'cuestionario.*.respuestas_por_usuario.*.respuesta'            => 'required_with:cuestionario.*.respuestas_por_usuario',
            'cuestionario.*.respuestas_por_usuario.*.fecha'                => 'nullable|date_format:Y-m-d',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique'               => 'Ya existe un test con este nombre.',
            'duracion_estimada.min'       => 'La duración estimada debe ser al menos 1 minuto.',
            'fechaAplicacion.date_format' => 'La fecha de aplicación debe tener el formato YYYY-MM-DD.',
        ];
    }
}
