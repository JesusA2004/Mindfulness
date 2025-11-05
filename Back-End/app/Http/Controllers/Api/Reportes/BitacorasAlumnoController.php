<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use App\Models\Bitacora;

class BitacorasAlumnoController extends BaseReportController
{
    public function index(Request $r)
    {
        $q = Bitacora::query();
        $q = $this->rangoFechas($r, 'fecha', $q);

        $alumnoParam = $this->d($r, 'alumno');
        $idsUser = $this->userIdsByAlumnoSearch($alumnoParam);

        if ($alumnoParam !== null && empty($idsUser)) {
            return response()->json(['rows'=>[]]);
        }
        if (!empty($idsUser)) {
            $q->whereIn('alumno_id', $this->mixedIn($idsUser));
        }

        $bits = $q->get(['alumno_id']);
        $conteo = [];
        foreach ($bits as $b) {
            $uid = (string)($b->alumno_id ?? '');
            if ($uid==='') continue;
            $conteo[$uid] = ($conteo[$uid] ?? 0) + 1;
        }

        $personaByUser = $this->personaMapByUserIds(array_keys($conteo));

        $rows = [];
        foreach ($conteo as $uid => $total) {
            $per = $personaByUser->get($uid);
            $rows[] = [
                'alumno'    => $per ? trim("{$per->nombre} {$per->apellidoPaterno} {$per->apellidoMaterno}") : 'Alumno',
                'matricula' => $per->matricula ?? '—',
                'total'     => (int)$total,
            ];
        }
        return response()->json(['rows'=>array_values($rows)]);
    }
}
