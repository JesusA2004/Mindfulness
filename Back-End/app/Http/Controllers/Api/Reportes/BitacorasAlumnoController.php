<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use App\Models\Bitacora;

class BitacorasAlumnoController extends BaseReportController
{
    /**
     * GET /api/reportes/bitacoras-por-alumno
     * Query params: desde, hasta
     * Response:
     *   { rows:[{ alumno, matricula, total }], total:int, meta:{ rango:string } }
     */
    public function index(Request $r)
    {
        $desde = $this->d($r, 'desde');
        $hasta = $this->d($r, 'hasta');

        // Traemos lo necesario SIN filtrar por fecha en la DB para no perder registros
        // cuando el campo `fecha` sea string o Date. Filtramos en PHP (Carbon parse).
        $bits = Bitacora::query()
            ->get(['alumno_id', 'fecha', 'created_at']);

        $inRange = static function ($rawFecha, $createdAt) use ($desde, $hasta): bool {
            // Normaliza: preferimos 'fecha' (puede ser 'YYYY-MM-DD' string), fallback a created_at
            $candidate = null;

            try {
                if ($rawFecha !== null && $rawFecha !== '') {
                    // Soporta string 'Y-m-d' o DateTime
                    $candidate = \Carbon\Carbon::parse((string)$rawFecha);
                }
            } catch (\Throwable $e) {
                $candidate = null;
            }

            if ($candidate === null) {
                try {
                    $candidate = \Carbon\Carbon::parse($createdAt);
                } catch (\Throwable $e) {
                    return false;
                }
            }

            if (!$desde && !$hasta) return true;

            if ($desde) {
                $ini = \Carbon\Carbon::parse($desde)->startOfDay();
                if ($candidate->lt($ini)) return false;
            }
            if ($hasta) {
                $end = \Carbon\Carbon::parse($hasta)->endOfDay();
                if ($candidate->gt($end)) return false;
            }
            return true;
        };

        // Conteo por alumno
        $conteo = [];            // user_id => total
        $total = 0;

        foreach ($bits as $b) {
            $uid = (string)($b->alumno_id ?? '');
            if ($uid === '') continue;

            $f = $b->fecha ?? null;
            $c = $b->created_at ?? null;

            if (!$inRange($f, $c)) continue;

            $conteo[$uid] = ($conteo[$uid] ?? 0) + 1;
            $total++;
        }

        if (empty($conteo)) {
            return response()->json([
                'rows' => [],
                'total'=> 0,
                'meta' => [
                    'rango' => ($desde && $hasta) ? ($desde.' a '.$hasta) : 'Todas las fechas',
                ],
            ]);
        }

        // Mapeo user -> persona para nombres y matrícula
        $personaByUser = $this->personaMapByUserIds(array_keys($conteo));

        $rows = [];
        foreach ($conteo as $uid => $t) {
            $per = $personaByUser->get($uid);
            $nombre = 'Alumno';
            $mat = '—';
            if ($per) {
                $nombre = trim(($per->nombre ?? '').' '.($per->apellidoPaterno ?? '').' '.($per->apellidoMaterno ?? ''));
                $mat = $per->matricula ?? '—';
            }
            $rows[] = [
                'alumno'    => $nombre !== '' ? $nombre : 'Alumno',
                'matricula' => $mat,
                'total'     => (int) $t,
            ];
        }

        // Orden descendente por total
        usort($rows, fn($a,$b) => $b['total'] <=> $a['total']);

        return response()->json([
            'rows'  => array_values($rows),
            'total' => (int)$total,
            'meta'  => [
                'rango' => ($desde && $hasta) ? ($desde.' a '.$hasta) : 'Todas las fechas',
            ],
        ]);
    }
}
