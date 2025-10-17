<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitaResource extends JsonResource
{
    
    public function toArray($request)
    {
        // Helpers para nombre completo (soporta distintos esquemas)
        $alumno = $this->whenLoaded('alumno');
        $docente = $this->whenLoaded('docente');

        $alumnoNombre = $alumno
            ? ($alumno->nombre_completo
                ?? $alumno->name
                ?? trim(($alumno->nombre ?? '') . ' ' . ($alumno->apellido ?? $alumno->apellidos ?? '')))
            : null;

        $docenteNombre = $docente
            ? ($docente->nombre_completo
                ?? $docente->name
                ?? trim(($docente->nombre ?? '') . ' ' . ($docente->apellido ?? $docente->apellidos ?? '')))
            : null;

        // Ids en string para evitar problemas de comparación (Mongo/ObjectId/numérico)
        $id = (string)($this->_id ?? $this->id);
        $alumnoId = (string)($this->alumno_id);
        $docenteId = (string)($this->docente_id);

        return [
            'id'             => $id,
            'alumno_id'      => $alumnoId,
            'docente_id'     => $docenteId,

            // Nombres listos para el front
            'alumno_nombre'  => $alumnoNombre,
            'docente_nombre' => $docenteNombre,

            'fecha_cita'     => optional($this->fecha_cita)->toISOString()
                                ?? (is_string($this->fecha_cita) ? $this->fecha_cita : null),
            'modalidad'      => $this->modalidad,
            'motivo'         => $this->motivo,
            'estado'         => $this->estado,

            'created_at'     => optional($this->created_at)->toISOString(),
            'updated_at'     => optional($this->updated_at)->toISOString(),
        ];
    }

}
