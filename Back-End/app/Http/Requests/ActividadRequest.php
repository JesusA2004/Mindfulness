<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActividadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Fechas
            'fechaAsignacion'   => 'nullable|date_format:Y-m-d',
            'fechaFinalizacion' => 'nullable|date_format:Y-m-d',
            'fechaMaxima'       => [
                'required',
                'date_format:Y-m-d',
                // La comparación con asignación no se valida aquí porque asignación la fija el backend a "hoy".
            ],

            // Datos principales
            'nombre'      => 'required|string|max:150',
            'descripcion' => 'required|string',

            // Referencias
            'docente_id'  => 'required|string',
            'tecnica_id'  => 'required|string',

            // Participantes
            'participantes'                 => 'sometimes|array|min:1',
            'participantes.*.user_id'       => 'required_with:participantes|string',
            'participantes.*.estado'        => 'required_with:participantes|in:Pendiente,Completado,Omitido',
        ];
    }

    public function messages(): array
    {
        return [
            'fechaAsignacion.date_format' => 'La fecha de asignación debe tener el formato YYYY-MM-DD.',
            'fechaFinalizacion.date_format' => 'La fecha de finalización debe tener el formato YYYY-MM-DD.',
            'fechaMaxima.required'        => 'La fecha máxima es obligatoria.',
            'fechaMaxima.date_format'     => 'La fecha máxima debe tener el formato YYYY-MM-DD.',

            'nombre.required'             => 'El nombre de la actividad es obligatorio.',
            'nombre.max'                  => 'El nombre no puede exceder 150 caracteres.',
            'descripcion.required'        => 'La descripción es obligatoria.',

            'docente_id.required'         => 'Debe indicar el docente.',
            'tecnica_id.required'         => 'Debe indicar la técnica.',

            'participantes.array'         => 'Los participantes deben enviarse como un arreglo.',
            'participantes.min'           => 'Debe indicar al menos un participante.',
            'participantes.*.user_id.required_with' => 'Cada participante debe incluir su user_id.',
            'participantes.*.estado.required_with'  => 'Cada participante debe incluir un estado.',
            'participantes.*.estado.in'   => 'El estado de participante debe ser Pendiente, Completado u Omitido.',
        ];
    }
}
