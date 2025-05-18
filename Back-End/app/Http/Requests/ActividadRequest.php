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
        // Al editar, podrías capturar el ID de la actividad:
        $actividadId = $this->route('actividad');

        return [
            // Fechas
            'fechaAsignacion'    => 'required|date_format:Y-m-d',
            'fechaFinalizacion'  => 'required|date_format:Y-m-d|after_or_equal:fechaAsignacion',
            'fechaMaxima'        => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:fechaAsignacion',
                'before_or_equal:fechaFinalizacion',
            ],

            // Datos principales
            'nombre'             => 'required|string|max:150',
            'descripcion'        => 'required|string',

            // Referencias
            'docente_id'         => 'required|exists:users,_id',
            'tecnica_id'         => 'required|exists:tecnicas,_id',

            // Participantes (opcional)
            'participantes'               => 'sometimes|array|min:1',
            'participantes.*.user_id'     => 'required_with:participantes|exists:users,_id',
            'participantes.*.estado'      => 'required_with:participantes|string|in:Pendiente,Completado,Omitido',
        ];
    }

    public function messages(): array
    {
        return [
            // Fechas
            'fechaAsignacion.date_format'       => 'La fecha de asignación debe tener el formato YYYY-MM-DD.',
            'fechaFinalizacion.after_or_equal'  => 'La fecha de finalización no puede ser anterior a la de asignación.',
            'fechaMaxima.after_or_equal'        => 'La fecha máxima no puede ser anterior a la de asignación.',
            'fechaMaxima.before_or_equal'       => 'La fecha máxima no puede superar la fecha de finalización.',

            // Referencias
            'docente_id.exists'                 => 'El docente especificado no está registrado.',
            'tecnica_id.exists'                 => 'La técnica especificada no existe.',

            // Participantes
            'participantes.array'               => 'Los participantes deben enviarse como un arreglo.',
            'participantes.min'                 => 'Debe indicar al menos un participante.',
            'participantes.*.user_id.exists'    => 'Cada participante debe ser un usuario válido.',
            'participantes.*.estado.in'         => 'El estado de participante debe ser Pendiente, Completado u Omitido.',
        ];
    }
}
