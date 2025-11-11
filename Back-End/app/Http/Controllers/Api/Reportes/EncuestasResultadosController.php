<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use App\Models\Encuesta;

class EncuestasResultadosController extends BaseReportController
{
    /**
     * GET /api/reportes/encuestas-resultados
     * Query params: desde, hasta, encuesta (opcional: id o título like)
     * Response JSON:
     * {
     *   labels: [string],
     *   data:   [int],           // total de respuestas por encuesta
     *   total:  int,             // suma de todos
     *   rows:   [{ encuesta, total }],
     *   meta:   {
     *     rango: string,
     *     encuesta: string|null,
     *     chartType: "vbar",
     *     chartData: [{ label, value, pct }]
     *   }
     * }
     */
    public function index(Request $r)
    {
        // ===== Filtros =====
        $desde    = trim((string)$r->query('desde', ''));
        $hasta    = trim((string)$r->query('hasta', ''));
        $encQuery = trim((string)$r->query('encuesta', ''));

        // No filtro por fecha en DB (puede venir string); filtro en PHP con Carbon
        $q = Encuesta::query();
        if ($encQuery !== '') {
            $q->where(function ($w) use ($encQuery) {
                $w->where('_id', $encQuery)
                  ->orWhere('titulo', 'like', "%{$encQuery}%");
            });
        }

        // Trae un set razonable
        $encuestas = $q->limit(50)->get(['_id','titulo','cuestionario','fechaFinalizacion','created_at']);

        $inRange = static function ($rawFechaFinal, $createdAt) use ($desde,$hasta): bool {
            // Intenta con fechaFinalizacion, si no, created_at
            $candidate = null;
            try {
                if ($rawFechaFinal !== null && $rawFechaFinal !== '') {
                    $candidate = Carbon::parse((string)$rawFechaFinal);
                }
            } catch (\Throwable $e) { $candidate = null; }

            if ($candidate === null) {
                try { $candidate = Carbon::parse($createdAt); }
                catch (\Throwable $e) { return false; }
            }

            if (!$desde && !$hasta) return true;

            if ($desde) {
                $ini = Carbon::parse($desde)->startOfDay();
                if ($candidate->lt($ini)) return false;
            }
            if ($hasta) {
                $end = Carbon::parse($hasta)->endOfDay();
                if ($candidate->gt($end)) return false;
            }
            return true;
        };

        $labels = [];
        $data   = [];
        $rows   = [];
        $total  = 0;

        foreach ($encuestas as $e) {
            if (!$inRange($e->fechaFinalizacion ?? null, $e->created_at ?? null)) continue;

            // Estructura flexible del cuestionario
            $cuest = is_array($e->cuestionario ?? null) ? $e->cuestionario : [];
            $count = 0;

            foreach ($cuest as $preg) {
                // Compat:
                // - respuestas_por_usuario => array
                // - respuestas => array | int (conteo)
                $rpu = Arr::get($preg, 'respuestas_por_usuario', null);
                $rsp = Arr::get($preg, 'respuestas', null);

                if (is_array($rpu)) {
                    $count += count($rpu);
                } elseif (is_array($rsp)) {
                    $count += count($rsp);
                } elseif (is_numeric($rsp)) {
                    $count += (int)$rsp;
                }
            }

            $titulo = $this->s($e->titulo) ?: 'Encuesta';
            $labels[] = $titulo;
            $data[]   = (int) $count;
            $rows[]   = [ 'encuesta' => $titulo, 'total' => (int)$count ];

            $total += (int)$count;
        }

        // Ordena por total desc para tabla y barras
        $paired = [];
        foreach ($labels as $i=>$lbl) $paired[] = ['label'=>$lbl, 'value'=>$data[$i] ?? 0];
        usort($paired, fn($a,$b) => ($b['value'] <=> $a['value']));

        // Reemplaza orden
        $labels = array_map(fn($x) => $x['label'], $paired);
        $data   = array_map(fn($x) => (int)$x['value'], $paired);
        $rows   = array_map(function($x){ return ['encuesta'=>$x['label'], 'total'=>(int)$x['value']]; }, $paired);

        // ChartData para PDF/preview
        $chartData = array_map(function($x) use ($total) {
            $v = (int)$x['value'];
            $pct = $total ? round(($v / $total) * 100, 1) : 0.0;
            return ['label'=>$x['label'], 'value'=>$v, 'pct'=>$pct];
        }, $paired);

        return response()->json([
            'labels' => $labels,
            'data'   => $data,
            'total'  => (int)$total,
            'rows'   => $rows,
            'meta'   => [
                'rango'     => ($desde && $hasta) ? ($desde.' a '.$hasta) : 'Todas las fechas',
                'encuesta'  => $encQuery !== '' ? $encQuery : null,
                'chartType' => 'vbar',
                'chartData' => $chartData,
            ],
        ]);
    }
}
