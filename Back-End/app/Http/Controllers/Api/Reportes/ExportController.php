<?php

namespace App\Http\Controllers\Api\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use MongoDB\BSON\ObjectId;

// MODELOS
use App\Models\Tecnica;
use App\Models\Actividad;
use App\Models\Cita;
use App\Models\Bitacora;
use App\Models\Encuesta;
use App\Models\Recompensa;
use App\Models\Persona;
use App\Models\User;

class ExportController extends BaseReportController
{
    public function export(Request $r)
    {
        // Aquí "tipo" es el tipo de exportación (pdf|excel)
        $tipo    = strtolower($this->d($r, 'tipo', 'pdf'));
        $reporte = $this->d($r, 'reporte', '');

        $title = '';
        $rows  = [];
        $meta  = [];

        switch ($reporte) {
            case 'top-tecnicas': {
                $payload = app(TopTecnicasController::class)->index($r)->getData(true);
                $title   = 'Top cuatro técnicas más utilizadas';

                $labels = $payload['labels']     ?? [];
                $data   = $payload['data']       ?? [];
                $dates  = $payload['usageDates'] ?? [];
                $total  = 0;
                foreach ($data as $v) { $total += (int)$v; }
                $total = max(0, (int)$total);

                $meta['chartType'] = 'vbar';
                $meta['chartData'] = [];

                foreach ($labels as $i => $label) {
                    $cnt = (int)($data[$i] ?? 0);
                    $pct = ($total > 0) ? round(($cnt / $total) * 100, 1) : 0.0;

                    $rows[] = [
                        'Técnica'    => (string)$label,
                        'Total'      => $cnt,
                        'Porcentaje' => $pct.' %',
                    ];

                    $meta['chartData'][] = [
                        'label' => (string)$label,
                        'value' => $cnt,
                        'pct'   => $pct,
                        'dates' => (isset($dates[$i]) && is_array($dates[$i])) ? $dates[$i] : [],
                    ];
                }

                $payloadMeta   = $payload['meta'] ?? [];
                $meta['rango'] = $payloadMeta['rango']
                                 ?? (($this->d($r,'desde') && $this->d($r,'hasta'))
                                        ? ($this->d($r,'desde').' a '.$this->d($r,'hasta'))
                                        : 'Todas las fechas');

                $coh = $payloadMeta['cohorte'] ?? $this->d($r,'grupo');
                if (!empty($payloadMeta['cohortes']) && is_array($payloadMeta['cohortes'])) {
                    $meta['Cohortes'] = implode(', ', array_values(array_unique(array_filter(
                        $payloadMeta['cohortes'],
                        fn($x) => trim((string)$x) !== ''
                    ))));
                }
                if ($coh) $meta['Cohorte'] = $coh;
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

                // filas tal cual vienen del controlador (recurso / puntaje / total)
                $rows    = $payload['rows'] ?? [];

                // meta con chartData + ejes que ya armamos en el controlador
                $meta    = $payload['meta'] ?? [];

                // Rango por si quieres sobreescribir si no viene
                if (!isset($meta['rango'])) {
                    $desde = $this->d($r,'desde'); $hasta = $this->d($r,'hasta');
                    $meta['rango'] = ($desde && $hasta) ? "$desde a $hasta" : 'Todas las fechas';
                }

                break;
            }

            case 'recompensas-canjeadas': {
                $payload = app(RecompensasCanjeadasController::class)->index($r)->getData(true);
                $title   = 'Recompensas canjeadas';
                $rows    = $payload['rows'] ?? [];

                // Chip específico con el nombre de recompensa, si se filtró
                $recName = $this->d($r, 'recompensa') ?? $this->d($r, 'nombre');
                if ($recName) {
                    $meta['Recompensa'] = $recName;
                }
                break;
            }

            default:
                return response()->json(['error' => 'Reporte no reconocido'], 400);
        }

        // Metadatos comunes (solo añade si no vinieron ya del caso particular)
        if (!isset($meta['rango'])) {
            $desde = $this->d($r,'desde'); $hasta = $this->d($r,'hasta');
            $meta['rango'] = ($desde && $hasta) ? "$desde a $hasta" : 'Todas las fechas';
        }
        if (!isset($meta['Cohorte']) && ($g = $this->d($r,'grupo')))   $meta['Cohorte'] = $g;
        if ($a = $this->d($r,'alumno'))   $meta['Alumno']   = $a;

        // Aquí "Tipo" se refiere al tipo de export (pdf|excel)
        if ($t = $this->d($r,'tipo'))     $meta['Tipo']     = $t;

        if ($tipo === 'excel')  return $this->exportExcel($title, $rows);
        if ($tipo === 'pdf')    return $this->exportPdf($title, $rows, $meta);

        return response()->json(['error' => 'Tipo de exportación inválido'], 400);
    }
}
