<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Obtén el ID de la cita para update (si aplica)
        $citaId = $this->route('cita');

        return [
            // Ambos deben existir en users y ser IDs válidos de MongoDB
            'alumno_id'    => 'required|exists:users,_id',
            'docente_id'   => 'required|exists:users,_id',

            // Fecha y hora de la cita, formato ISO 8601 compatible
            'fecha_cita'   => 'required|date_format:Y-m-d\TH:i:sP',

            // Modalidad de la cita
            'modalidad'    => 'required|string|in:Presencial,Virtual',

            // Motivo opcional
            'motivo'       => 'nullable|string',

            // Estado al crear puede omitirse (usa default), pero si se envía:
            'estado'       => 'sometimes|string|in:Pendiente,Aceptada,Rechazada,Finalizada',
        ];
    }

    public function messages(): array
    {
        return [
            'alumno_id.required'              => 'Debe indicar el alumno que solicita la cita.',
            'alumno_id.exists'                => 'El alumno seleccionado no está registrado.',
            'docente_id.required'             => 'Debe indicar el docente asignado a la cita.',
            'docente_id.exists'               => 'El docente seleccionado no está registrado.',
            'fecha_cita.required'             => 'La fecha y hora de la cita son obligatorias.',
            'fecha_cita.date_format'          => 'La fecha debe tener el formato ISO 8601 (ej. 2025-05-18T14:30:00+00:00).',
            'modalidad.required'              => 'La modalidad de la cita es obligatoria.',
            'modalidad.in'                    => 'La modalidad debe ser "Presencial" o "Virtual".',
            'motivo.string'                   => 'El motivo debe ser un texto válido.',
            'estado.in'                       => 'El estado debe ser uno de: Pendiente, Aceptada, Rechazada o Finalizada.',
        ];
    }
}
