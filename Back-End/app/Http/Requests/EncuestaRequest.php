<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EncuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Capturamos el ID de la encuesta en caso de update
        $encuestaId = $this->route('encuesta');

        return [
            // 1. Campos principales
            'titulo'              => "required|string|max:150|unique:encuestas,titulo,{$encuestaId}",
            'descripcion'         => 'nullable|string',
            'fechaAsignacion'     => 'required|date_format:Y-m-d',
            'fechaFinalizacion'   => 'required|date_format:Y-m-d|after_or_equal:fechaAsignacion',
            'duracion_estimada'   => 'required|integer|min:1',

            // 2. Array de cuestionario (opcional)
            'cuestionario'                 => 'sometimes|array|min:1',
            // Cada elemento debe tener campo pregunta
            'cuestionario.*.pregunta'      => 'required_with:cuestionario|string|max:255',
            // respuestas debe ser array
            'cuestionario.*.respuestas'    => 'required_with:cuestionario|array|min:1',
            // cada respuesta es string
            'cuestionario.*.respuestas.*'  => 'string|max:200',
            // id_usuario debe existir en users
            'cuestionario.*.id_usuario'    => 'required_with:cuestionario|exists:users,_id',
        ];
    }

    public function messages(): array
    {
        return [
            // Títulos
            'titulo.unique'                   => 'Ya existe una encuesta con ese título.',
            // Fechas
            'fechaAsignacion.date_format'    => 'La fecha de asignación debe usar el formato YYYY-MM-DD.',
            'fechaFinalizacion.date_format'  => 'La fecha de finalización debe usar el formato YYYY-MM-DD.',
            'fechaFinalizacion.after_or_equal'=> 'La fecha de finalización no puede ser anterior a la de asignación.',
            // Cuestionario
            'cuestionario.array'             => 'El cuestionario debe ser un arreglo de preguntas.',
            'cuestionario.min'               => 'Debe incluir al menos una pregunta en el cuestionario.',
            'cuestionario.*.pregunta.max'    => 'Cada pregunta no debe superar 255 caracteres.',
            'cuestionario.*.respuestas.array'=> 'Las respuestas deben enviarse como un arreglo.',
            'cuestionario.*.respuestas.min'  => 'Cada pregunta debe tener al menos una respuesta.',
            'cuestionario.*.respuestas.*.string' => 'Cada respuesta debe ser texto de hasta 200 caracteres.',
            'cuestionario.*.id_usuario.exists'   => 'El usuario que responde no está registrado.',
        ];
    }
}
