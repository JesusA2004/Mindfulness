<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Tecnica;

class ActividadesAlumnoController extends BaseReportController
{
    public function index(Request $r)
    {
        $q = Actividad::query();
        $campoFecha = 'fechaMaxima';
        $q = $this->rangoFechas($r, $campoFecha, $q);

        $alumnoParam = $this->d($r, 'alumno');
        $idsAlumno = $this->userIdsByAlumnoSearch($alumnoParam);

        if ($alumnoParam !== null && empty($idsAlumno)) {
            return response()->json(['rows'=>[]]);
        }
        if (!empty($idsAlumno)) {
            $q->where('participantes', 'elemMatch', [
                'user_id' => ['$in' => $this->mixedIn($idsAlumno)]
            ]);
        }

        $acts = $q->latest($campoFecha)->limit(500)
            ->get(['nombre','descripcion','tecnica_id',$campoFecha,'participantes']);

        $tecMap = Tecnica::whereIn('_id', $acts->pluck('tecnica_id')->filter()->unique()->values()->all())
            ->get(['_id','nombre'])->keyBy('_id');

        $userIds = [];
        foreach ($acts as $a) foreach ((array)$a->participantes as $p) $userIds[] = (string)($p['user_id'] ?? '');
        $personaByUser = $this->personaMapByUserIds(array_values(array_unique($userIds)));

        $rows = [];
        foreach ($acts as $a) {
            $tec   = optional($tecMap->get((string)$a->tecnica_id))->nombre ?: '—';
            $fecha = $a->{$campoFecha} ?? $a->created_at;

            foreach ((array)$a->participantes as $p) {
                $uid = (string)($p['user_id'] ?? '');
                if ($uid === '') continue;
                if (!empty($idsAlumno) && !in_array($uid, $idsAlumno, true)) continue;

                $per = $personaByUser->get($uid);
                $alumnoNom = $per ? trim("{$per->nombre} {$per->apellidoPaterno} {$per->apellidoMaterno}") : 'Alumno';
                $mat = $per->matricula ?? '—';

                $rows[] = [
                    'alumno'      => $alumnoNom,
                    'matricula'   => $mat,
                    'tecnica'     => $tec,
                    'fecha'       => $fecha,
                    'titulo'      => (string)($a->nombre ?? ''),
                    'descripcion' => (string)($a->descripcion ?? ''),
                ];
            }
        }
        return response()->json(['rows'=>$rows]);
    }
}
