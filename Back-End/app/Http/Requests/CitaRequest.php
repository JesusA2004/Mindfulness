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
        // $citaId = $this->route('cita'); // por si lo necesitas en el futuro

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

            // Estado (si se envía). Por regla, "Pendiente" = aún no vista
            'estado'       => 'sometimes|string|in:Pendiente,Aceptada,Rechazada,Finalizada',

            // NUEVO: observaciones
            'observaciones'=> 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'alumno_id.required'     => 'Debe indicar el alumno que solicita la cita.',
            'alumno_id.exists'       => 'El alumno seleccionado no está registrado.',
            'docente_id.required'    => 'Debe indicar el docente asignado a la cita.',
            'docente_id.exists'      => 'El docente seleccionado no está registrado.',
            'fecha_cita.required'    => 'La fecha y hora de la cita son obligatorias.',
            'fecha_cita.date_format' => 'La fecha debe tener el formato ISO 8601 (ej. 2025-05-18T14:30:00+00:00).',
            'modalidad.required'     => 'La modalidad de la cita es obligatoria.',
            'modalidad.in'           => 'La modalidad debe ser "Presencial" o "Virtual".',
            'motivo.string'          => 'El motivo debe ser un texto válido.',
            'estado.in'              => 'El estado debe ser uno de: Pendiente, Aceptada, Rechazada o Finalizada.',
            'observaciones.string'   => 'Las observaciones deben ser texto.',
            'observaciones.max'      => 'Las observaciones no deben exceder 2000 caracteres.',
        ];
    }
}
