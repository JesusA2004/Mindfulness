<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use App\Models\Cita;

class CitasAlumnoController extends BaseReportController
{
    public function index(Request $r)
    {
        $q = Cita::query();
        $q = $this->rangoFechas($r, 'fecha_cita', $q);

        $alumnoParam = $this->d($r, 'alumno');
        $idsUser = $this->userIdsByAlumnoSearch($alumnoParam);

        if ($alumnoParam !== null && empty($idsUser)) {
            return response()->json(['rows'=>[]]);
        }
        if (!empty($idsUser)) {
            $q->whereIn('alumno_id', $this->mixedIn($idsUser));
        }

        $citas = $q->orderBy('fecha_cita', 'desc')->limit(600)
            ->get(['alumno_id','fecha_cita','estado','motivo']);

        $personaByUser = $this->personaMapByUserIds(
            $citas->pluck('alumno_id')->map(fn($v)=>(string)$v)->unique()->values()->all()
        );

        $rows = [];
        foreach ($citas as $c) {
            $per = $personaByUser->get((string)$c->alumno_id);
            $rows[] = [
                'alumno'    => $per ? trim("{$per->nombre} {$per->apellidoPaterno} {$per->apellidoMaterno}") : 'Alumno',
                'matricula' => $per->matricula ?? '—',
                'fecha'     => $c->fecha_cita ?? $c->created_at,
                'estado'    => (string)($c->estado ?? 'Pendiente'),
                'motivo'    => (string)($c->motivo ?? ''),
            ];
        }
        return response()->json(['rows'=>$rows]);
    }
}
