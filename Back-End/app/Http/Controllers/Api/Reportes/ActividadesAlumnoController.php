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

        // === Campo de fecha (string YYYY-MM-DD en la BD) ===
        $campoFecha = 'fechaMaxima';

        // ====== RANGO DE FECHAS (funciona sólo con strings YYYY-MM-DD) ======
        $desde = $this->d($r, 'desde');
        $hasta = $this->d($r, 'hasta');

        if ($desde) {
            $q->where($campoFecha, '>=', $desde);
        }
        if ($hasta) {
            $q->where($campoFecha, '<=', $hasta);
        }

        // ====== FILTRO DE ALUMNO ======
        // Puede llegar matrícula ("M2025-00045") o texto libre
        $alumnoParam = $this->d($r, 'alumno');
        $norm        = $this->normalizeAlumnoNeedle($alumnoParam);
        $matFilter   = $norm['mat'];     // si viene matrícula
        $tokens      = $norm['tokens'];  // si viene nombre / cohorte

        // Traemos actividades (ya filtradas por fecha)
        $acts = $q->latest($campoFecha)
            ->limit(500)
            ->get(['nombre','descripcion','tecnica_id',$campoFecha,'participantes']);

        // ====== MAPA DE TÉCNICAS ======
        $tecMap = Tecnica::whereIn('_id', $acts->pluck('tecnica_id')->filter()->unique()->values()->all())
            ->get(['_id','nombre'])
            ->keyBy('_id');

        // ====== PERSONAS POR USER_ID ======
        $userIds = [];
        foreach ($acts as $a) {
            foreach ((array) $a->participantes as $p) {
                $userIds[] = (string) ($p['user_id'] ?? '');
            }
        }
        $personaByUser = $this->personaMapByUserIds(array_values(array_unique($userIds)));

        // ====== CONSTRUCCIÓN DE FILAS (aquí sí filtramos por alumno) ======
        $rows = [];

        foreach ($acts as $a) {
            $tec   = optional($tecMap->get((string) $a->tecnica_id))->nombre ?: '—';
            $fecha = $a->{$campoFecha} ?? $a->created_at;

            foreach ((array) $a->participantes as $p) {
                $uid = (string) ($p['user_id'] ?? '');
                if ($uid === '') {
                    continue;
                }

                $per = $personaByUser->get($uid);

                $alumnoNom = $per
                    ? trim("{$per->nombre} {$per->apellidoPaterno} {$per->apellidoMaterno}")
                    : 'Alumno';

                $mat = $per->matricula ?? '—';

                // ---------- APLICAR FILTRO DE ALUMNO ----------
                if ($alumnoParam !== null) {
                    $include = true;

                    // 1) Si vino matrícula (caso del modal: "M2025-00045")
                    if ($matFilter !== null) {
                        $include = stripos($mat, $matFilter) !== false;
                    }
                    // 2) Si vino nombre / texto libre (por si escribes a mano)
                    elseif (!empty($tokens) && $per) {
                        $haystack = trim(
                            ($per->nombre ?? '') . ' ' .
                            ($per->apellidoPaterno ?? '') . ' ' .
                            ($per->apellidoMaterno ?? '') . ' ' .
                            ($per->cohorte ?? '')
                        );
                        $haystack = function_exists('mb_strtolower')
                            ? mb_strtolower($haystack, 'UTF-8')
                            : strtolower($haystack);

                        foreach ($tokens as $t) {
                            $tNorm = function_exists('mb_strtolower')
                                ? mb_strtolower($t, 'UTF-8')
                                : strtolower($t);

                            if ($tNorm === '') {
                                continue;
                            }

                            if (strpos($haystack, $tNorm) === false) {
                                $include = false;
                                break;
                            }
                        }
                    }

                    if (!$include) {
                        continue; // no pasa el filtro, saltamos este participante
                    }
                }
                // ---------- FIN FILTRO DE ALUMNO ----------

                $rows[] = [
                    'alumno'      => $alumnoNom,
                    'matricula'   => $mat,
                    'tecnica'     => $tec,
                    'fecha'       => $fecha,
                    'titulo'      => (string) ($a->nombre ?? ''),
                    'descripcion' => (string) ($a->descripcion ?? ''),
                ];
            }
        }

        return response()->json(['rows' => $rows]);
    }
}
