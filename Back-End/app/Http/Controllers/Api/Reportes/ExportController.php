<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;

class ExportController extends BaseReportController
{
    public function export(Request $r)
    {
        $tipo    = strtolower($this->d($r, 'tipo', 'pdf'));
        $reporte = $this->d($r, 'reporte', '');

        $title = '';
        $rows = [];
        $meta = [];

        switch ($reporte) {
            case 'top-tecnicas': {
                $payload = app(TopTecnicasController::class)->index($r)->getData(true);
                $title   = 'Top cuatro técnicas más utilizadas';

                $labels = $payload['labels'] ?? [];
                $data   = $payload['data']   ?? [];
                $total  = max(1, (int)($payload['total'] ?? 0));

                $meta['chartData'] = [];
                $meta['chartType'] = 'vbar'; // vertical

                foreach ($labels as $i => $label) {
                    $cnt = (int)($data[$i] ?? 0);
                    $pct = round(($cnt / $total) * 100, 1);
                    $rows[] = ['Técnica'=>(string)$label, 'Total'=>$cnt, 'Porcentaje'=>$pct.' %'];
                    $meta['chartData'][] = ['label'=>$label, 'pct'=>$pct, 'value'=>$cnt];
                }
                break;
            }
            case 'actividades-por-alumno': {
                $payload = app(ActividadesAlumnoController::class)->index($r)->getData(true);
                $title   = 'Actividades por alumno';
                $rows    = $payload['rows'] ?? [];
                break;
            }
            case 'citas-por-alumno': {
                $payload = app(CitasAlumnoController::class)->index($r)->getData(true);
                $title   = 'Citas por alumno';
                $rows    = $payload['rows'] ?? [];
                break;
            }
            case 'bitacoras-por-alumno': {
                $payload = app(BitacorasAlumnoController::class)->index($r)->getData(true);
                $title   = 'Bitácoras por alumno';
                $rows    = $payload['rows'] ?? [];
                break;
            }
            case 'encuestas-resultados': {
                $payload = app(EncuestasResultadosController::class)->index($r)->getData(true);
                $title   = 'Resultados de encuestas';
                $rows    = $this->rowsFromChart($payload, 'Encuesta', 'Respuestas');
                break;
            }
            case 'recompensas-canjeadas': {
                $payload = app(RecompensasCanjeadasController::class)->index($r)->getData(true);
                $title   = 'Recompensas canjeadas';
                $rows    = $payload['rows'] ?? [];
                break;
            }
            default:
                return response()->json(['error'=>'Reporte no reconocido'], 400);
        }

        $desde = $this->d($r,'desde'); $hasta = $this->d($r,'hasta');
        $meta['rango'] = ($desde && $hasta) ? "$desde a $hasta" : 'Todas las fechas';

        if ($g = $this->d($r,'grupo'))    $meta['Cohorte']   = $g;
        if ($a = $this->d($r,'alumno'))   $meta['Alumno']    = $a;
        if ($e = $this->d($r,'encuesta')) $meta['Encuesta']  = $e;
        if ($t = $this->d($r,'tipo'))     $meta['Tipo']      = $t;

        if ($tipo === 'excel')  return $this->exportExcel($title, $rows);
        if ($tipo === 'pdf')    return $this->exportPdf($title, $rows, $meta);

        return response()->json(['error'=>'Tipo de exportación inválido'], 400);
    }
}
