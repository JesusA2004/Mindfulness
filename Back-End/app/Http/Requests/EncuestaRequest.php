<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EncuestaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $routeParam = $this->route('encuesta');
        $id = $routeParam instanceof \App\Models\Encuesta ? $routeParam->getKey() : $routeParam;

        return [
            'titulo'            => ['required','string','max:150', Rule::unique('encuestas','titulo')->ignore($id, '_id')],
            'descripcion'       => 'nullable|string',
            'fechaAsignacion'   => 'nullable|date_format:Y-m-d',
            'fechaFinalizacion' => 'required|date_format:Y-m-d|after_or_equal:fechaAsignacion',
            'duracion_estimada' => 'required|integer|min:1',

            'cuestionario'                 => 'sometimes|array|min:1',
            'cuestionario.*._id'           => 'required_with:cuestionario|string|max:50',
            'cuestionario.*.pregunta'      => 'required_with:cuestionario|string|max:255',
            'cuestionario.*.tipo'          => 'required_with:cuestionario|string|in:opcion_multiple,seleccion_multiple,respuesta_abierta',

            // opciones solo si no es abierta
            'cuestionario.*.opciones'      => 'nullable|array',
            'cuestionario.*.opciones.*'    => 'string|max:200',

            // respuestas_por_usuario es OPCIONAL al crear/editar
            'cuestionario.*.respuestas_por_usuario'      => 'nullable|array',
            'cuestionario.*.respuestas_por_usuario.*.usuario_id' => 'required_with:cuestionario.*.respuestas_por_usuario|string',
            'cuestionario.*.respuestas_por_usuario.*.respuesta'   => 'required_with:cuestionario.*.respuestas_por_usuario',
            'cuestionario.*.respuestas_por_usuario.*.fecha'       => 'nullable|date_format:Y-m-d',
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.unique' => 'Ya existe una encuesta con ese título.',
            'fechaFinalizacion.after_or_equal' => 'La fecha de finalización no puede ser anterior a la de asignación.',
        ];
    }
}
