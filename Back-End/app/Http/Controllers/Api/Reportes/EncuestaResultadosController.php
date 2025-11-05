<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;               // <- IMPORTANTE
use Carbon\Carbon;
use App\Models\Encuesta;

class EncuestasResultadosController extends BaseReportController
{
    public function index(Request $r)
    {
        $q = Encuesta::query();

        // Rango por fecha de cierre / finalización
        $desde = trim((string)$r->query('desde', ''));
        $hasta = trim((string)$r->query('hasta', ''));
        if ($desde) {
            try { $q->where('fechaFinalizacion', '>=', Carbon::parse($desde)->startOfDay()); } catch (\Throwable $e) {}
        }
        if ($hasta) {
            try { $q->where('fechaFinalizacion', '<=', Carbon::parse($hasta)->endOfDay()); } catch (\Throwable $e) {}
        }

        // Filtro por encuesta (id o título like)
        $enc = trim((string)$r->query('encuesta', ''));
        if ($enc !== '') {
            $q->where(function ($w) use ($enc) {
                $w->where('_id', $enc)->orWhere('titulo', 'like', "%{$enc}%");
            });
        }

        // Trae varias, arma barras por encuesta
        $encuestas = $q->limit(30)->get(['_id','titulo','cuestionario']);

        $labels = [];
        $data   = [];

        foreach ($encuestas as $e) {
            $total = 0;

            // $e->cuestionario: array de preguntas
            $cuest = is_array($e->cuestionario ?? null) ? $e->cuestionario : [];

            foreach ($cuest as $preg) {
                // Compatibilidad: algunos dumps guardan "respuestas_por_usuario",
                // otros "respuestas" (array), o un número total.
                $rpu = Arr::get($preg, 'respuestas_por_usuario', null);
                $rsp = Arr::get($preg, 'respuestas', null);

                if (is_array($rpu)) {
                    $total += count($rpu);
                } elseif (is_array($rsp)) {
                    $total += count($rsp);
                } elseif (is_numeric($rsp)) {
                    $total += (int)$rsp;
                }
            }

            $labels[] = (string)($e->titulo ?? 'Encuesta');
            $data[]   = (int)$total;
        }

        return response()->json(['labels' => $labels, 'data' => $data]);
    }
}
