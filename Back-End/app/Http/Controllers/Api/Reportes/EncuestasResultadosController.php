<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use App\Models\Tecnica;

class EncuestasResultadosController extends BaseReportController
{
    /**
     * GET /api/reportes/encuestas-resultados
     *
     * Filtros:
     *   - desde (Y-m-d)
     *   - hasta (Y-m-d)
     *
     * Usa Tecnica.calificaciones:
     *   { usuario_id, puntaje, comentario, fecha, _id }
     *
     * Tabla:
     *   - Recurso Mindfulness
     *   - Puntaje (1..5)
     *   - Total (n° de calificaciones con ese puntaje para ese recurso)
     *
     * Gráfica:
     *   - Conteo por puntaje (1..5), sumando la columna Total
     *     de las filas con ese puntaje.
     */
    public function index(Request $r)
    {
        $desde = trim((string) $r->query('desde', ''));
        $hasta = trim((string) $r->query('hasta', ''));

        // Traemos técnicas con calificaciones embebidas
        $tecnicas = Tecnica::query()
            ->limit(500)
            ->get(['_id', 'nombre', 'calificaciones']);

        // Mapa: recurso => [ puntaje => total ]
        $resourceStats = [];

        $inRange = function (?string $fechaStr) use ($desde, $hasta): bool {
            if (!$fechaStr) return false;
            try {
                $f = Carbon::parse($fechaStr);
            } catch (\Throwable $e) {
                return false;
            }

            if ($desde) {
                $ini = Carbon::parse($desde)->startOfDay();
                if ($f->lt($ini)) return false;
            }
            if ($hasta) {
                $end = Carbon::parse($hasta)->endOfDay();
                if ($f->gt($end)) return false;
            }
            return true;
        };

        // Extrae nombre de recurso desde el comentario "Recurso: X"
        $extractRecurso = function (?string $comentario): string {
            $c = trim((string) $comentario);
            if ($c === '') return 'Recurso sin nombre';

            $pos = stripos($c, 'recurso:');
            if ($pos !== false) {
                $after = substr($c, $pos + strlen('recurso:'));
                $name  = trim($after);
                return $name !== '' ? $name : 'Recurso sin nombre';
            }
            return $c;
        };

        // Recorremos todas las calificaciones
        foreach ($tecnicas as $t) {
            $califs = is_array($t->calificaciones ?? null) ? $t->calificaciones : [];

            foreach ($califs as $c) {
                $puntaje = (int) Arr::get($c, 'puntaje', 0);
                if ($puntaje < 1 || $puntaje > 5) continue;

                $fechaStr = Arr::get($c, 'fecha', null);
                if (!$inRange($fechaStr)) continue;

                $recursoName = $extractRecurso(Arr::get($c, 'comentario', ''));

                if (!isset($resourceStats[$recursoName])) {
                    $resourceStats[$recursoName] = [];
                }
                if (!isset($resourceStats[$recursoName][$puntaje])) {
                    $resourceStats[$recursoName][$puntaje] = 0;
                }
                $resourceStats[$recursoName][$puntaje]++;
            }
        }

        // === Tabla: una fila por (recurso, puntaje) ===
        $rows = [];
        foreach ($resourceStats as $name => $byScore) {
            foreach ($byScore as $score => $count) {
                $rows[] = [
                    'Recurso Mindfulness' => (string) $name,
                    'Puntaje'             => (int) $score,
                    'Total'               => (int) $count,
                ];
            }
        }

        // Orden: por recurso (A-Z) y puntaje desc
        usort($rows, function ($a, $b) {
            $cmp = strcmp($a['Recurso Mindfulness'], $b['Recurso Mindfulness']);
            if ($cmp === 0) {
                return $b['Puntaje'] <=> $a['Puntaje'];
            }
            return $cmp;
        });

        // === Conteo para gráfica a partir de la tabla ===
        $scoreCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $total = 0;

        foreach ($rows as $row) {
            $score = (int) ($row['Puntaje'] ?? 0);
            $cnt   = (int) ($row['Total'] ?? 0);

            if ($score < 1 || $score > 5) continue;

            $scoreCounts[$score] += $cnt;
            $total += $cnt;
        }

        $labels = [];
        $data   = [];
        $chartData = [];

        foreach ([1,2,3,4,5] as $p) {
            $count = (int) ($scoreCounts[$p] ?? 0);
            $labels[] = (string) $p;
            $data[]   = $count;

            $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0.0;

            // etiqueta con estrellita para la gráfica
            $chartData[] = [
                'label' => $p.'★',
                'value' => $count,
                'pct'   => $pct,
            ];
        }

        return response()->json([
            'labels' => $labels,
            'data'   => $data,
            'total'  => (int) $total,
            'rows'   => $rows,
            'meta'   => [
                'rango'     => ($desde && $hasta) ? ($desde.' a '.$hasta) : 'Todas las fechas',
                'chartType' => 'vbar',
                'chartData' => $chartData,
                'axisY'     => 'Eje Y: Conteo de calificaciones',
                'axisX'     => 'Eje X: Número de estrellas (puntaje)',
            ],
        ]);
    }
}
