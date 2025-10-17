<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CitaResource extends JsonResource
{
    public function toArray($request)
    {
        // Relaciones (si fueron cargadas con ->load('alumno','docente'))
        $alumno  = $this->whenLoaded('alumno');
        $docente = $this->whenLoaded('docente');

        // Nombre completo tolerante a distintos esquemas
        $alumnoNombre = $alumno
            ? (
                $alumno->nombre_completo
                ?? $alumno->name
                ?? trim(implode(' ', array_filter([$alumno->nombre ?? null, $alumno->apellido ?? $alumno->apellidos ?? null])))
            )
            : null;

        $docenteNombre = $docente
            ? (
                $docente->nombre_completo
                ?? $docente->name
                ?? trim(implode(' ', array_filter([$docente->nombre ?? null, $docente->apellido ?? $docente->apellidos ?? null])))
            )
            : null;

        return [
            // Ids como string (compatibilidad Mongo/ObjectId)
            'id'              => (string)($this->_id ?? $this->id),
            'alumno_id'       => (string)$this->alumno_id,
            'docente_id'      => (string)$this->docente_id,

            // Nombres derivados de las relaciones (si están cargadas)
            'alumno_nombre'   => $alumnoNombre,
            'docente_nombre'  => $docenteNombre,

            // Datos de la cita
            'fecha_cita'      => $this->formatIso($this->fecha_cita),
            'modalidad'       => $this->modalidad,
            'motivo'          => $this->motivo,
            'estado'          => $this->estado,
            'observaciones'   => $this->observaciones,  // <-- agregado

            // Timestamps
            'created_at'      => $this->formatIso($this->created_at),
            'updated_at'      => $this->formatIso($this->updated_at),
        ];
    }

    /**
     * Devuelve fecha en ISO-8601:
     * - Carbon: format('c')
     * - DateTimeInterface: format('c')
     * - string: tal cual
     * - null/otros: null
     */
    protected function formatIso($value): ?string
    {
        if (!$value) return null;
        if (is_string($value)) return $value;
        if (method_exists($value, 'format')) return $value->format('c'); // Carbon / DateTimeInterface
        return null;
        // Alternativamente: (string)$value; pero preferimos null si no es fecha válida
    }
}
