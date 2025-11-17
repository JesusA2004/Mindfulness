<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use App\Models\Cita;

class CitasAlumnoController extends BaseReportController
{
    public function index(Request $r)
    {
        $q = Cita::query();

        // ===== Campo de fecha (datetime en Mongo) =====
        $campoFecha = 'fecha_cita';

        // ===== Rango de fechas usando helper genérico (Carbon) =====
        $q = $this->rangoFechas($r, $campoFecha, $q);

        // ===== Filtro de alumno (nombre / matrícula) =====
        $alumnoParam = $this->d($r, 'alumno'); // puede venir vacío, matrícula o texto
        $norm        = $this->normalizeAlumnoNeedle($alumnoParam);
        $matFilter   = $norm['mat'];     // si detectamos matrícula
        $tokens      = $norm['tokens'];  // si detectamos nombre/cohorte

        // Traemos citas filtradas por fecha
        $citas = $q->orderBy($campoFecha, 'desc')->limit(600)
            ->get(['alumno_id', $campoFecha, 'estado', 'motivo']);

        // Mapa user_id -> persona (nombre, apellidos, matrícula)
        $personaByUser = $this->personaMapByUserIds(
            $citas->pluck('alumno_id')->map(fn($v) => (string) $v)->unique()->values()->all()
        );

        $rows = [];

        foreach ($citas as $c) {
            $uid = (string) ($c->alumno_id ?? '');
            if ($uid === '') {
                continue;
            }

            $per = $personaByUser->get($uid);

            $alumnoNom = $per
                ? trim("{$per->nombre} {$per->apellidoPaterno} {$per->apellidoMaterno}")
                : 'Alumno';

            $mat = $per->matricula ?? '—';

            // ---------- FILTRO DE ALUMNO (igual lógica que actividades) ----------
            if ($alumnoParam !== null) {
                $include = true;

                // 1) Si vino matrícula (caso normal desde el modal: "M2025-00045")
                if ($matFilter !== null) {
                    $include = stripos($mat, $matFilter) !== false;
                }
                // 2) Si vino nombre / cohorte escrito a mano
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
                    continue; // no pasa el filtro de alumno
                }
            }
            // ---------- FIN FILTRO DE ALUMNO ----------

            $rows[] = [
                'alumno'    => $alumnoNom,
                'matricula' => $mat,
                'fecha'     => $c->{$campoFecha} ?? $c->created_at,
                'estado'    => (string) ($c->estado ?? 'Pendiente'),
                'motivo'    => (string) ($c->motivo ?? ''),
            ];
        }

        return response()->json(['rows' => $rows]);
    }
}
