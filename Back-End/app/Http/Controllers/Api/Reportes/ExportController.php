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
        $rows  = [];
        $meta  = [];

        switch ($reporte) {
            case 'top-tecnicas': {
                // Obtenemos el payload del endpoint index (ya aplica Top-4, filtros y usageDates)
                $payload = app(TopTecnicasController::class)->index($r)->getData(true);
                $title   = 'Top cuatro técnicas más utilizadas';

                $labels = $payload['labels']     ?? [];
                $data   = $payload['data']       ?? [];
                $dates  = $payload['usageDates'] ?? []; // paralelo a labels/data
                // Total real para porcentajes (suma de dataset)
                $total  = 0;
                foreach ($data as $v) { $total += (int)$v; }
                $total = max(0, (int)$total);

                // Gráfica vertical PDF-safe
                $meta['chartType'] = 'vbar';
                $meta['chartData'] = [];

                foreach ($labels as $i => $label) {
                    $cnt = (int)($data[$i] ?? 0);
                    $pct = ($total > 0) ? round(($cnt / $total) * 100, 1) : 0.0;

                    // Filas de tabla
                    $rows[] = [
                        'Técnica'    => (string)$label,
                        'Total'      => $cnt,
                        'Porcentaje' => $pct.' %',
                    ];

                    // Barras del chart del PDF (con fechas)
                    $meta['chartData'][] = [
                        'label' => (string)$label,
                        'value' => $cnt,
                        'pct'   => $pct,
                        'dates' => (isset($dates[$i]) && is_array($dates[$i])) ? $dates[$i] : [],
                    ];
                }

                // Chips de encabezado (rango/cohorte): prioriza lo que venga del payload->meta
                $payloadMeta   = $payload['meta'] ?? [];
                $meta['rango'] = $payloadMeta['rango']
                                 ?? (($this->d($r,'desde') && $this->d($r,'hasta'))
                                        ? ($this->d($r,'desde').' a '.$this->d($r,'hasta'))
                                        : 'Todas las fechas');

                $coh = $payloadMeta['cohorte'] ?? $this->d($r,'grupo');
                if (!empty($payloadMeta['cohortes']) && is_array($payloadMeta['cohortes'])) {
                    $meta['Cohortes'] = implode(', ', array_values(array_unique(array_filter($payloadMeta['cohortes'], fn($x)=>trim((string)$x) !== ''))));
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
                $rows    = $this->rowsFromChart($payload, 'Encuesta', 'Respuestas');
                // Chips base
                $desde = $this->d($r,'desde'); $hasta = $this->d($r,'hasta');
                $meta['rango'] = ($desde && $hasta) ? "$desde a $hasta" : 'Todas las fechas';
                if ($e = $this->d($r,'encuesta')) $meta['Encuesta'] = $e;
                break;
            }

            case 'recompensas-canjeadas': {
                $payload = app(RecompensasCanjeadasController::class)->index($r)->getData(true);
                $title   = 'Recompensas canjeadas';
                $rows    = $payload['rows'] ?? [];
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
        if ($t = $this->d($r,'tipo'))     $meta['Tipo']     = $t;

        // Export
        if ($tipo === 'excel')  return $this->exportExcel($title, $rows);
        if ($tipo === 'pdf')    return $this->exportPdf($title, $rows, $meta);

        return response()->json(['error' => 'Tipo de exportación inválido'], 400);
    }
}
