<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class CitaResource extends JsonResource
{
    public function toArray($request)
    {
        // Relaciones cargadas
        $alumno  = $this->whenLoaded('alumno');
        $docente = $this->whenLoaded('docente');

        // Proteger acceso a persona
        $alumnoPersona  = ($alumno instanceof MissingValue) ? null : $alumno?->persona;
        $docentePersona = ($docente instanceof MissingValue) ? null : $docente?->persona;

        $alumnoNombre  = $this->nombreCompuesto($alumno, $alumnoPersona);
        $docenteNombre = $this->nombreCompuesto($docente, $docentePersona);

        return [
            'id'              => (string)($this->_id ?? $this->id),
            'alumno_id'       => (string)$this->alumno_id,
            'docente_id'      => (string)$this->docente_id,
            'alumno_nombre'   => $alumnoNombre,
            'docente_nombre'  => $docenteNombre,
            'fecha_cita'      => $this->formatIso($this->fecha_cita),
            'modalidad'       => $this->modalidad,
            'motivo'          => $this->motivo,
            'estado'          => $this->estado,
            'observaciones'   => $this->observaciones,
            'created_at'      => $this->formatIso($this->created_at),
            'updated_at'      => $this->formatIso($this->updated_at),
        ];
    }

    protected function nombreCompuesto($user, $persona = null): ?string
    {
        if (!$user || $user instanceof MissingValue) return null;

        if ($persona) {
            $nombre = trim(implode(' ', array_filter([
                $persona->nombre ?? null,
                $persona->apellidoPaterno ?? null,
                $persona->apellidoMaterno ?? null,
            ])));
            if ($nombre !== '') return $nombre;
        }

        $nombre = $user->nombre_completo
            ?? $user->name
            ?? trim(implode(' ', array_filter([
                $user->nombre ?? null,
                $user->apellido ?? $user->apellidos ?? null,
            ])));

        return $nombre !== '' ? $nombre : null;
    }

    protected function formatIso($value): ?string
    {
        if (!$value) return null;
        if (is_string($value)) return $value;
        if (method_exists($value, 'format')) return $value->format('c');
        return null;
    }
}
