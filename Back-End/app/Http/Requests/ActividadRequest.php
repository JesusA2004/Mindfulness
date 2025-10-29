<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Si usas políticas, cámbialo por el check correspondiente.
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'        => ['required','string','max:150'],
            'descripcion'   => ['required','string','max:1000'],

            // Fechas en formato YYYY-MM-DD. after_or_equal:today usa la tz del servidor;
            // si tu app trabaja en America/Mexico_City y el server en UTC,
            // puedes validar en controlador con Carbon si quieres ultra-precisión.
            'fechaMaxima'   => ['required','date','after_or_equal:today'],

            // IDs en string (no forzamos ObjectId para no chocar con el front)
            'docente_id'    => ['required','string'],
            'tecnica_id'    => ['required','string'],

            // Participantes: array de objetos { user_id, estado? }
            'participantes'               => ['required','array','min:1'],
            'participantes.*.user_id'     => ['required','string','distinct'],
            'participantes.*.estado'      => [Rule::in(['Pendiente','Completado','Omitido'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'              => 'El nombre es obligatorio.',
            'nombre.max'                   => 'El nombre no debe exceder 150 caracteres.',
            'descripcion.required'         => 'La descripción es obligatoria.',
            'fechaMaxima.required'         => 'La fecha máxima es obligatoria.',
            'fechaMaxima.date'             => 'La fecha máxima no tiene un formato válido.',
            'fechaMaxima.after_or_equal'   => 'La fecha máxima debe ser hoy o posterior.',
            'docente_id.required'          => 'Falta el docente.',
            'tecnica_id.required'          => 'Debes seleccionar una técnica.',
            'participantes.required'       => 'Debes asignar al menos un participante.',
            'participantes.array'          => 'Participantes debe ser una lista.',
            'participantes.*.user_id.required' => 'Falta el alumno en la asignación.',
            'participantes.*.user_id.distinct' => 'Hay alumnos repetidos en la asignación.',
            'participantes.*.estado.in'    => 'Estado de participante inválido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $trim = fn($v) => is_string($v) ? trim($v) : $v;

        $this->merge([
            'nombre'      => $trim($this->input('nombre')),
            'descripcion' => $trim($this->input('descripcion')),
            'fechaMaxima' => $trim($this->input('fechaMaxima')),
            'docente_id'  => (string) $this->input('docente_id'),
            'tecnica_id'  => (string) $this->input('tecnica_id'),
        ]);

        // Normaliza participantes -> strings y estado válido
        $part = $this->input('participantes', []);
        if (is_array($part)) {
            $norm = [];
            foreach ($part as $p) {
                $uid = isset($p['user_id']) ? (string) $p['user_id'] : '';
                $est = $p['estado'] ?? 'Pendiente';
                if (!in_array($est, ['Pendiente','Completado','Omitido'], true)) $est = 'Pendiente';
                $norm[] = ['user_id' => $uid, 'estado' => $est];
            }
            $this->merge(['participantes' => $norm]);
        }
    }
}
