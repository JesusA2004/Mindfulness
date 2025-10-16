<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Fechas
            'fechaAsignacion'   => 'required|date_format:Y-m-d',
            'fechaFinalizacion' => 'required|date_format:Y-m-d|after_or_equal:fechaAsignacion',
            'fechaMaxima'       => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:fechaAsignacion',
                'before_or_equal:fechaFinalizacion',
            ],

            // Datos principales
            'nombre'            => 'required|string|max:150',
            'descripcion'       => 'required|string',

            // Referencias (validación simple, SIN ObjectId ni exists)
            'docente_id'        => 'required|string',
            'tecnica_id'        => 'required|string',

            // Participantes (opcional)
            'participantes'                 => 'sometimes|array|min:1',
            'participantes.*.user_id'       => 'required_with:participantes|string',
            'participantes.*.estado'        => 'required_with:participantes|in:Pendiente,Completado,Omitido',
        ];
    }

    public function messages(): array
    {
        return [
            // Fechas
            'fechaAsignacion.required'         => 'La fecha de asignación es obligatoria.',
            'fechaAsignacion.date_format'      => 'La fecha de asignación debe tener el formato YYYY-MM-DD.',
            'fechaFinalizacion.required'       => 'La fecha de finalización es obligatoria.',
            'fechaFinalizacion.date_format'    => 'La fecha de finalización debe tener el formato YYYY-MM-DD.',
            'fechaFinalizacion.after_or_equal' => 'La fecha de finalización no puede ser anterior a la de asignación.',
            'fechaMaxima.required'             => 'La fecha máxima es obligatoria.',
            'fechaMaxima.date_format'          => 'La fecha máxima debe tener el formato YYYY-MM-DD.',
            'fechaMaxima.after_or_equal'       => 'La fecha máxima no puede ser anterior a la de asignación.',
            'fechaMaxima.before_or_equal'      => 'La fecha máxima no puede superar la fecha de finalización.',

            // Datos principales
            'nombre.required'                  => 'El nombre de la actividad es obligatorio.',
            'nombre.max'                       => 'El nombre no puede exceder 150 caracteres.',
            'descripcion.required'             => 'La descripción es obligatoria.',

            // Referencias
            'docente_id.required'              => 'Debe indicar el docente.',
            'tecnica_id.required'              => 'Debe indicar la técnica.',

            // Participantes
            'participantes.array'              => 'Los participantes deben enviarse como un arreglo.',
            'participantes.min'                => 'Debe indicar al menos un participante.',
            'participantes.*.user_id.required_with'
                                               => 'Cada participante debe incluir su user_id.',
            'participantes.*.estado.required_with'
                                               => 'Cada participante debe incluir un estado.',
            'participantes.*.estado.in'        => 'El estado de participante debe ser Pendiente, Completado u Omitido.',
        ];
    }
}
