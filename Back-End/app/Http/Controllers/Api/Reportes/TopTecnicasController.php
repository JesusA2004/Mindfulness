<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Tecnica;

class TopTecnicasController extends BaseReportController
{
    /**
     * GET /api/reportes/top-tecnicas
     * Query params: desde, hasta, grupo (cohorte)
     * Response JSON:
     * {
     *   labels: [string],
     *   data:   [int],
     *   total:  int,
     *   rows:   [{ tecnica, total, porcentaje }]
     * }
     */
    public function index(Request $r)
    {
        $q = Actividad::query();
        $campoFecha = 'fechaMaxima';
        $q = $this->rangoFechas($r, $campoFecha, $q);

        // Filtro por cohorte/grupo (buscando por persona->user_id en participantes)
        if (($grupoParam = $this->d($r, 'grupo')) !== null) {
            $userIds = $this->userIdsByCohorte($grupoParam);
            if (empty($userIds)) {
                return response()->json([
                    'labels' => [], 'data' => [], 'total' => 0, 'rows' => []
                ]);
            }
            $q->where('participantes', 'elemMatch', [
                'user_id' => ['$in' => $this->mixedIn($userIds)]
            ]);
        }

        // Contar ocurrencias por técnica
        $rows = $q->get(['tecnica_id']);
        $conteo = [];
        foreach ($rows as $row) {
            $tid = (string)($row->tecnica_id ?? '');
            if ($tid === '') continue;
            $conteo[$tid] = ($conteo[$tid] ?? 0) + 1;
        }

        // Top 4
        $labels = []; $data = []; $tableRows = [];
        $totalUsos = array_sum($conteo);

        if (!empty($conteo)) {
            arsort($conteo);
            $top = array_slice($conteo, 0, 4, true);

            $tecs = Tecnica::whereIn('_id', array_keys($top))
                ->get(['_id','nombre'])->keyBy('_id');

            foreach ($top as $tid => $cnt) {
                $name = $this->s(optional($tecs->get($tid))->nombre) ?: 'Técnica';
                $pct  = $totalUsos > 0 ? round(($cnt / $totalUsos) * 100, 1) : 0.0;

                $labels[] = $name;
                $data[]   = (int)$cnt;
                $tableRows[] = [
                    'tecnica'    => $name,
                    'total'      => (int)$cnt,
                    'porcentaje' => $pct, // 0.0 – 100.0
                ];
            }
        }

        return response()->json([
            'labels' => $labels,
            'data'   => $data,
            'total'  => (int)$totalUsos,
            'rows'   => $tableRows,
        ]);
    }
}
