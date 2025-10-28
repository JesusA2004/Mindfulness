<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Tecnica;
use App\Models\Bitacora;
use App\Models\Persona;
use App\Models\Actividad;
use App\Models\Cita;
use App\Models\Recompensa;

use Illuminate\Support\Carbon;
use MongoDB\BSON\Regex;
use MongoDB\BSON\ObjectId;
use Exception;

class DashboardController extends Controller
{
    /* ============================================================
     |                         ADMIN
     * ============================================================ */
    public function overview(Request $request)
    {
        try {
            $todayMx = Carbon::now('America/Mexico_City')->toDateString();

            $totalEstudiantes = User::whereIn('rol', ['estudiante','alumno'])->count();
            $totalDocentes    = User::where('rol', 'profesor')->count();
            $totalUsuarios    = $totalEstudiantes + $totalDocentes;
            $totalTecnicas    = Tecnica::count();

            $bitacorasHoy     = Bitacora::where('fecha', $todayMx)->count();
            $bitacorasTotales = Bitacora::count();

            return response()->json([
                'usuariosTotales'  => (int) $totalUsuarios,
                'estudiantes'      => (int) $totalEstudiantes,
                'docentes'         => (int) $totalDocentes,
                'totalTecnicas'    => (int) $totalTecnicas,
                'bitacorasHoy'     => (int) $bitacorasHoy,
                'bitacorasTotales' => (int) $bitacorasTotales,
                'hoy'              => $todayMx,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'usuariosTotales'  => 0,
                'estudiantes'      => 0,
                'docentes'         => 0,
                'totalTecnicas'    => 0,
                'bitacorasHoy'     => 0,
                'bitacorasTotales' => 0,
                'hoy'              => Carbon::now('America/Mexico_City')->toDateString(),
            ], 200);
        }
    }

    public function bitacorasPorMes(Request $request)
    {
        $year   = (int) ($request->query('year') ?: date('Y'));
        $labels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

        try {
            $pipeline = [
                [
                    '$match' => [
                        'fecha' => [
                            '$type'  => 'string',
                            '$regex' => '^' . $year . '\-',
                        ],
                    ],
                ],
                [
                    '$project' => [
                        '_id'   => 0,
                        'month' => [
                            '$toInt' => [
                                '$substrBytes' => ['$fecha', 5, 2]
                            ]
                        ],
                    ],
                ],
                [
                    '$group' => [
                        '_id'   => ['m' => '$month'],
                        'count' => ['$sum' => 1],
                    ],
                ],
                [
                    '$project' => [
                        '_id'   => 0,
                        'month' => '$_id.m',
                        'count' => 1,
                    ],
                ],
                [ '$sort' => ['month' => 1] ],
            ];

            $cursor = Bitacora::raw(fn($c) => $c->aggregate($pipeline));

            $countsByMonth = array_fill(1, 12, 0);
            foreach ($cursor as $doc) {
                $m = (int) ($doc['month'] ?? 0);
                $c = (int) ($doc['count'] ?? 0);
                if ($m >= 1 && $m <= 12) $countsByMonth[$m] = $c;
            }

            return response()->json([
                'labels' => $labels,
                'data'   => array_values($countsByMonth),
                'year'   => $year,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'labels' => $labels,
                'data'   => array_fill(0, 12, 0),
                'year'   => $year,
            ], 200);
        }
    }

    /* ============================================================
     |                        PROFESOR
     * ============================================================ */
    public function profesorOverview(Request $request)
    {
        try {
            $todayMx = Carbon::now('America/Mexico_City')->toDateString();
            $prof    = $request->user();

            if (!$prof) {
                return response()->json([
                    'hoy' => $todayMx, 'cohortes' => [], 'alumnosCargo' => 0,
                ], 200);
            }

            [$cohortes, $regexes] = $this->resolveCohortesForUser($prof);

            if (!empty($regexes)) {
                [$personaIdStrings, $personaIdObjects] = $this->personaIdsBothTypesByCohortesRegex($regexes);

                $alumnos = 0;
                if (!empty($personaIdStrings) || !empty($personaIdObjects)) {
                    $alumnos = User::where(function ($q) use ($personaIdObjects, $personaIdStrings) {
                            if (!empty($personaIdObjects)) $q->whereIn('persona_id', $personaIdObjects);
                            if (!empty($personaIdStrings)) $q->orWhereIn('persona_id', $personaIdStrings);
                        })
                        ->whereIn('rol', ['estudiante','alumno'])
                        ->count();
                }

                return response()->json([
                    'hoy'          => $todayMx,
                    'cohortes'     => $cohortes,
                    'alumnosCargo' => (int) $alumnos,
                ], 200);
            }

            [$profStr, ] = $this->idBothTypes($prof->id ?? $prof->_id ?? null);
            $alumnosUnicos = 0;

            if ($profStr && class_exists(Cita::class)) {
                $rows = Cita::where('docente_id', $profStr)->get(['alumno_id']);
                $set  = [];
                foreach ($rows as $r) {
                    $aid = (string) ($r->alumno_id ?? '');
                    if ($aid !== '') $set[$aid] = true;
                }
                $alumnosUnicos = count($set);
            }

            return response()->json([
                'hoy'          => $todayMx,
                'cohortes'     => [],
                'alumnosCargo' => (int) $alumnosUnicos,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'hoy'          => Carbon::now('America/Mexico_City')->toDateString(),
                'cohortes'     => [],
                'alumnosCargo' => 0,
            ], 200);
        }
    }

    public function profesorCalendario(Request $request)
    {
        try {
            $prof = $request->user();
            [$profStr, ] = $this->idBothTypes($prof->id ?? $prof->_id ?? null);
            [$cohortes, $regexes] = $this->resolveCohortesForUser($prof);

            $start = $request->query('start') ?: Carbon::now('America/Mexico_City')->startOfMonth()->toDateString();
            $end   = $request->query('end')   ?: Carbon::now('America/Mexico_City')->endOfMonth()->toDateString();

            $items = [];

            if ($profStr && class_exists(Cita::class)) {
                $items = Cita::where('docente_id', $profStr)
                    ->whereBetween('fecha_cita', [
                        $start . 'T00:00:00+00:00',
                        $end   . 'T23:59:59+00:00'
                    ])
                    ->get()
                    ->map(function ($c) {
                        return [
                            'titulo'      => (string)($c->motivo ?? 'Cita'),
                            'fecha'       => (string)($c->fecha_cita ?? ''),
                            'descripcion' => (string)($c->observaciones ?? ''),
                            'cohorte'     => null,
                        ];
                    })
                    ->values()
                    ->all();
            }

            if (empty($items) && class_exists(Actividad::class)) {
                $acts = Actividad::all()->filter(function ($a) use ($start, $end, $regexes) {
                    $coh = (string) ($a->cohorte ?? $a->grupo ?? '');
                    if (!empty($regexes) && !$this->stringMatchesAnyRegex($coh, $regexes)) return false;

                    $dates = array_filter([
                        $a->fecha ?? null,
                        $a->fecha_inicio ?? null,
                        $a->fechaFin ?? null,
                        $a->fechaAsignacion ?? null,
                    ]);

                    foreach ($dates as $d) if ($d >= $start && $d <= $end) return true;
                    return false;
                })->values()->all();

                $items = array_map(function ($a) {
                    return [
                        'titulo'      => $a->titulo ?? ($a->nombre ?? 'Actividad'),
                        'fecha'       => $a->fecha ?? ($a->fecha_inicio ?? ($a->fechaFin ?? ($a->fechaAsignacion ?? null))),
                        'descripcion' => $a->descripcion ?? ($a->detalle ?? ''),
                        'cohorte'     => $a->cohorte ?? ($a->grupo ?? null),
                    ];
                }, $acts);
            }

            usort($items, fn($a, $b) => strtotime($a['fecha'] ?? '') <=> strtotime($b['fecha'] ?? ''));

            return response()->json([
                'items' => $items,
                'start' => $start,
                'end'   => $end,
            ], 200);

        } catch (Exception $e) {
            return response()->json(['items' => []], 200);
        }
    }

    public function profesorActividadesPorGrupo(Request $request)
    {
        try {
            $prof = $request->user();
            [$cohortes, $regexes] = $this->resolveCohortesForUser($prof);

            $counts = [];
            if (class_exists(Actividad::class)) {
                foreach (Actividad::all() as $a) {
                    $coh = (string) ($a->cohorte ?? $a->grupo ?? '');
                    if ($coh === '') continue;
                    if (!empty($regexes) && !$this->stringMatchesAnyRegex($coh, $regexes)) continue;

                    $key = strtoupper(trim($coh));
                    $counts[$key] = ($counts[$key] ?? 0) + 1;
                }
            }

            if (empty($counts)) {
                $labels = $cohortes;
                $data   = array_fill(0, count($cohortes), 0);
                return response()->json(['labels' => $labels, 'data' => $data], 200);
            }

            ksort($counts);
            return response()->json([
                'labels' => array_keys($counts),
                'data'   => array_values($counts),
            ], 200);

        } catch (Exception $e) {
            return response()->json(['labels'=>[], 'data'=>[]], 200);
        }
    }

    /* ============================================================
     |                          ALUMNO
     * ============================================================ */
    public function alumnoOverview(Request $request)
    {
        try {
            $today = Carbon::now('America/Mexico_City')->toDateString();
            $alumno = $request->user();

            if (!$alumno) {
                return response()->json([
                    'hoy' => $today,
                    'tecnicasRealizadas' => 0,
                    'citasPendientesMes' => 0,
                    'recompensas' => 0
                ], 200);
            }

            // IDs del usuario activo en ambos formatos posibles
            [$userStr, $userObj]       = $this->idBothTypes($alumno->id ?? $alumno->_id ?? null);
            [$personaStr, $personaObj] = $this->idBothTypes($alumno->persona_id ?? null);

            /* === Técnicas realizadas (bitácoras del alumno) === */
            // Cobertura por todos los campos que has usado en tus colecciones
            $bitacoras = Bitacora::all();
            $tecnicasRealizadas = $bitacoras->filter(function ($b) use ($userStr, $personaStr) {
                $cands = [
                    (string)($b->alumno_id ?? ''),     (string)($b->persona_id ?? ''),
                    (string)($b->user_id ?? ''),       (string)($b->estudiante_id ?? '')
                ];
                return in_array($userStr, $cands, true) || in_array($personaStr, $cands, true);
            })->count();

            /* === Citas pendientes del mes para el alumno === */
            $inicioMes = Carbon::now('America/Mexico_City')->startOfMonth()->toDateString();
            $finMes    = Carbon::now('America/Mexico_City')->endOfMonth()->toDateString();
            $citasPendientes = 0;

            if (class_exists(Cita::class)) {
                $citasPendientes = Cita::whereBetween('fecha_cita', [
                        $inicioMes . 'T00:00:00+00:00',
                        $finMes    . 'T23:59:59+00:00'
                    ])
                    ->get()
                    ->filter(function ($c) use ($userStr, $personaStr) {
                        $estado = strtolower((string)($c->estado ?? ''));
                        $pend   = in_array($estado, ['pendiente','programada','agendada','por atender','pending']) || $estado === '';
                        if (!$pend) return false;

                        $cands = [
                            (string)($c->alumno_id ?? ''), (string)($c->persona_id ?? ''),
                            (string)($c->user_id ?? ''),   (string)($c->paciente_id ?? '')
                        ];
                        return in_array($userStr, $cands, true) || in_array($personaStr, $cands, true);
                    })
                    ->count();
            }

            /* === Recompensas obtenidas ===
               Campo real: "canjeo" (a veces string JSON "[]", a veces array, a veces vacío).
            */
            $recompensas = 0;
            if (class_exists(Recompensa::class)) {
                $idsBuscados = array_values(array_filter([$userStr, $personaStr]));
                foreach (Recompensa::all() as $r) {
                    $raw = $r->canjeo ?? null;

                    // Normaliza a arreglo
                    if (is_string($raw)) {
                        $raw = trim($raw);
                        $arr = $raw !== '' ? json_decode($raw, true) : [];
                        if (!is_array($arr)) $arr = [];
                    } elseif (is_array($raw)) {
                        $arr = $raw;
                    } else {
                        $arr = [];
                    }

                    foreach ($arr as $cj) {
                        $uids = [
                            (string)($cj['usuario_id'] ?? ''),
                            (string)($cj['alumno_id']  ?? ''),
                            (string)($cj['persona_id'] ?? '')
                        ];
                        if (array_intersect($idsBuscados, $uids)) {
                            $recompensas++;
                        }
                    }
                }
            }

            return response()->json([
                'hoy'                => $today,
                'tecnicasRealizadas' => (int) $tecnicasRealizadas,
                'citasPendientesMes' => (int) $citasPendientes,
                'recompensas'        => (int) $recompensas,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'hoy'                => Carbon::now('America/Mexico_City')->toDateString(),
                'tecnicasRealizadas' => 0,
                'citasPendientesMes' => 0,
                'recompensas'        => 0
            ], 200);
        }
    }

    /* ============================================================
     |                         HELPERS
     * ============================================================ */

    private function resolveCohortesForUser(?User $user): array
    {
        $list = [];

        if ($user && !empty($user->persona_id)) {
            $persona = Persona::find($user->persona_id);
            if ($persona) {
                $raw = $persona->cohorte ?? null;
                if (is_array($raw)) {
                    foreach ($raw as $c) { $s = trim((string)$c); if ($s !== '') $list[] = $s; }
                } elseif (is_string($raw)) {
                    $s = trim($raw); if ($s !== '') $list[] = $s;
                }
            }
        }

        $list = array_values(array_unique($list));

        $regexes = array_map(function ($c) {
            $quoted = preg_quote($c, '/');
            return new Regex('^' . $quoted . '$', 'i');
        }, $list);

        return [$list, $regexes];
    }

    private function personaIdsBothTypesByCohortesRegex(array $regexes): array
    {
        if (empty($regexes)) return [[], []];

        $cursor = Persona::raw(function($c) use ($regexes) {
            return $c->find(
                ['cohorte' => ['$in' => $regexes]],
                ['projection' => ['_id' => 1]]
            );
        });

        $asStrings = [];
        $asObjects = [];

        foreach ($cursor as $doc) {
            $id = $doc['_id'] ?? null;

            if ($id instanceof ObjectId) {
                $asObjects[] = $id;
                $asStrings[] = (string) $id;
            } elseif (is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id)) {
                $asStrings[] = $id;
                try { $asObjects[] = new ObjectId($id); } catch (\Throwable $e) {}
            }
        }

        $asStrings = array_values(array_unique($asStrings));
        return [$asStrings, $asObjects];
    }

    private function stringMatchesAnyRegex(string $value, array $regexes): bool
    {
        foreach ($regexes as $rx) {
            $pat = '/' . $rx->getPattern() . '/i';
            if (@preg_match($pat, $value) === 1) return true;
        }
        return false;
    }

    private function idBothTypes($id): array
    {
        $idStr = null; $idObj = null;
        if (is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $idStr = $id;
            try { $idObj = new ObjectId($id); } catch (\Throwable $e) {}
        } elseif ($id instanceof ObjectId) {
            $idObj = $id; $idStr = (string)$id;
        }
        return [$idStr, $idObj];
    }
}
