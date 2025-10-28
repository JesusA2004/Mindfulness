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
     |                       ADMIN (EXISTENTE)
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
     |                       PROFESOR
     * ============================================================ */

    // Endpoint de diagnóstico (útil mientras pruebas)
    public function profesorDebug(Request $request)
    {
        try {
            $prof = $request->user();
            [$cohortes, $regexes] = $this->resolveCohortesForUser($prof);
            [$idsStr, $idsObj]    = $this->personaIdsBothTypesByCohortesRegex($regexes);

            // Importante: rol dentro del mismo closure para no “romper” el OR
            $countEither = 0;
            if (!empty($idsStr) || !empty($idsObj)) {
                $countEither = User::where(function ($q) use ($idsObj, $idsStr) {
                        if (!empty($idsObj)) $q->whereIn('persona_id', $idsObj);
                        if (!empty($idsStr)) $q->orWhereIn('persona_id', $idsStr);
                    })
                    ->whereIn('rol', ['estudiante','alumno'])
                    ->count();
            }

            return response()->json([
                'user'                => $prof?->only(['_id','id','email','rol','persona_id']),
                'cohortesDetectados'  => $cohortes,
                'regexes'             => array_map(fn($r) => $r->getPattern(), $regexes),
                'personaIdsStrings'   => $idsStr,
                'personaIdsObjects'   => array_map(fn($o) => (string)$o, $idsObj),
                'countCombinado'      => $countEither,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['error'=>true,'message'=>$e->getMessage()], 200);
        }
    }

    public function profesorOverview(Request $request)
    {
        try {
            $todayMx = Carbon::now('America/Mexico_City')->toDateString();

            $prof = $request->user();
            if (!$prof) {
                return response()->json([
                    'hoy' => $todayMx, 'cohortes' => [], 'alumnosCargo' => 0,
                ], 200);
            }

            [$cohortes, $regexes] = $this->resolveCohortesForUser($prof);

            if (empty($regexes)) {
                return response()->json([
                    'hoy' => $todayMx, 'cohortes' => $cohortes, 'alumnosCargo' => 0,
                ], 200);
            }

            [$personaIdStrings, $personaIdObjects] = $this->personaIdsBothTypesByCohortesRegex($regexes);

            // ⚠️ Orden y agrupación: primero persona_id IN (obj|str) con OR; después rol IN (...)
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
            [$cohortes, $regexes] = $this->resolveCohortesForUser($prof);

            $start = $request->query('start') ?: Carbon::now('America/Mexico_City')->startOfMonth()->toDateString();
            $end   = $request->query('end')   ?: Carbon::now('America/Mexico_City')->endOfMonth()->toDateString();

            $items = [];

            if (class_exists(Cita::class)) {
                $items = Cita::raw(function($c) use ($regexes, $start, $end) {
                    $q = ['fecha' => ['$gte' => $start, '$lte' => $end]];
                    if (!empty($regexes)) $q['cohorte'] = ['$in' => $regexes];
                    return $c->find($q);
                })->toArray();

                $items = array_map(function($c) {
                    return [
                        'titulo'      => $c['titulo'] ?? ($c['asunto'] ?? 'Cita'),
                        'fecha'       => $c['fecha'] ?? ($c['fecha_inicio'] ?? ($c['fecha_cita'] ?? null)),
                        'descripcion' => $c['descripcion'] ?? ($c['detalle'] ?? ''),
                        'cohorte'     => $c['cohorte'] ?? ($c['grupo'] ?? null),
                    ];
                }, $items);
            }

            if (empty($items) && class_exists(Actividad::class)) {
                $acts = Actividad::all()->filter(function ($a) use ($start, $end, $regexes) {
                    $coh = (string) ($a->cohorte ?? $a->grupo ?? '');
                    if (!empty($regexes) && !$this->stringMatchesAnyRegex($coh, $regexes)) return false;

                    $dates = array_filter([
                        $a->fecha ?? null, $a->fecha_inicio ?? null,
                        $a->fechaFin ?? null, $a->fechaAsignacion ?? null,
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

            usort($items, function($a, $b) {
                $da = !empty($a['fecha']) ? strtotime($a['fecha']) : null;
                $db = !empty($b['fecha']) ? strtotime($b['fecha']) : null;
                if (!$da && !$db) return 0;
                if (!$da) return 1;
                if (!$db) return -1;
                return $da <=> $db;
            });

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
                return response()->json([
                    'labels' => $cohortes,
                    'data'   => array_fill(0, count($cohortes), 0),
                ], 200);
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
     |                       ALUMNO (NUEVO)
     * ============================================================ */

    // KPIs alumno + hoy
    public function alumnoOverview(Request $request)
    {
        try {
            $todayMx = Carbon::now('America/Mexico_City')->toDateString();
            $alumno  = $request->user();
            if (!$alumno) {
                return response()->json([
                    'hoy'=>$todayMx,'tecnicasRealizadas'=>0,'citasPendientesMes'=>0,'recompensas'=>0
                ],200);
            }

            [$idStr, $idObj] = $this->idBothTypes($alumno->id ?? $alumno->_id ?? null);
            [$personaStr, $personaObj] = $this->idBothTypes($alumno->persona_id ?? null);

            // Técnicas realizadas = bitácoras del alumno (si hay campo tecnica_id lo ignoramos, solo contar entradas)
            $bitacorasQuery = Bitacora::query();
            if ($personaObj) $bitacorasQuery->orWhere('alumno_id', $personaObj);
            if ($personaStr) $bitacorasQuery->orWhere('alumno_id', $personaStr);
            $tecnicasRealizadas = (int) $bitacorasQuery->count();

            // Citas pendientes del MES (por alumno o por cohorte)
            $inicioMes = Carbon::now('America/Mexico_City')->startOfMonth()->toDateString();
            $finMes    = Carbon::now('America/Mexico_City')->endOfMonth()->toDateString();

            $citasPendientes = 0;
            if (class_exists(Cita::class)) {
                // obtener cohorte del alumno para fallback
                $cohortesAlumno = $this->cohortesFromPersonaId($alumno->persona_id ?? null);
                $cohortRegexes  = array_map(fn($c)=> new Regex('^'.preg_quote($c,'/').'$','i'), $cohortesAlumno);

                $citasPendientes = Cita::raw(function($c) use ($inicioMes,$finMes,$idStr,$personaStr,$cohortRegexes){
                    $or = [];

                    if ($idStr) {
                        // por si citas guardan user_id
                        $or[] = ['user_id' => $idStr];
                        $or[] = ['alumno_id' => $idStr];
                    }
                    if ($personaStr) {
                        $or[] = ['persona_id' => $personaStr];
                        $or[] = ['alumno_id'   => $personaStr];
                    }
                    if (!empty($cohortRegexes)) {
                        $or[] = ['cohorte' => ['$in'=>$cohortRegexes]];
                    }

                    $match = [
                        '$and' => [
                            ['fecha' => ['$gte'=>$inicioMes, '$lte'=>$finMes]],
                            ['$or'  => $or],
                            ['estado' => ['$in' => ['pendiente','programada','agendada','por atender']]]
                        ]
                    ];
                    return $c->countDocuments($match);
                });
            }

            // Recompensas obtenidas (busca array canjeos con user_id/alumno_id)
            $recompensas = 0;
            if (class_exists(Recompensa::class)) {
                $recompensas = Recompensa::raw(function($c) use ($idStr,$personaStr){
                    $idCandidates = array_values(array_filter([$idStr,$personaStr]));
                    if (empty($idCandidates)) return 0;
                    $pipeline = [
                        [
                            '$project' => [
                                'cnt' => [
                                    '$size' => [
                                        '$filter' => [
                                            'input' => ['$ifNull' => ['$canjeos', []]],
                                            'as'    => 'cj',
                                            'cond'  => [
                                                '$or' => [
                                                    ['$$cj.user_id'   => ['$in' => $idCandidates]],
                                                    ['$$cj.alumno_id' => ['$in' => $idCandidates]],
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [ '$group' => ['_id' => null, 'total' => ['$sum' => '$cnt'] ] ]
                    ];
                    $res = $c->aggregate($pipeline)->toArray();
                    return !empty($res) ? ($res[0]['total'] ?? 0) : 0;
                });
            }

            return response()->json([
                'hoy'                 => $todayMx,
                'tecnicasRealizadas'  => (int) $tecnicasRealizadas,
                'citasPendientesMes'  => (int) $citasPendientes,
                'recompensas'         => (int) $recompensas,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'hoy'=>Carbon::now('America/Mexico_City')->toDateString(),
                'tecnicasRealizadas'=>0,'citasPendientesMes'=>0,'recompensas'=>0
            ],200);
        }
    }

    // Serie semanal (últimos 7 días). Si Bitácora tiene score/estado numérico, se promedia; si no, cuenta entradas (0/1).
    public function alumnoBienestarSemanal(Request $request)
    {
        try {
            $alumno = $request->user();
            [$personaStr, $personaObj] = $this->idBothTypes($alumno->persona_id ?? null);

            $hoy = Carbon::now('America/Mexico_City')->startOfDay();
            $labels = [];
            $data   = [];

            // Construir fechas últimos 7 días (lun-dom según locale MX)
            for ($i=6; $i>=0; $i--) {
                $d = $hoy->copy()->subDays($i);
                $labels[] = $d->locale('es_MX')->isoFormat('ddd');
            }

            // Traer bitácoras del rango
            $inicio = Carbon::now('America/Mexico_City')->subDays(6)->toDateString();
            $fin    = Carbon::now('America/Mexico_City')->toDateString();

            $q = Bitacora::whereBetween('fecha', [$inicio, $fin]);
            if ($personaObj) $q->orWhere('alumno_id', $personaObj);
            if ($personaStr) $q->orWhere('alumno_id', $personaStr);

            $rows = $q->get();

            // Detectar si hay campo numérico de bienestar (score/estadoValor/etc.)
            $valueKeys = ['score','puntuacion','nivel','estadoValor','bienestar','valor'];
            $hasNumeric = false;
            foreach ($rows as $r) {
                foreach ($valueKeys as $k) {
                    if (isset($r->$k) && is_numeric($r->$k)) { $hasNumeric = true; break 2; }
                }
            }

            // Agrupar por día
            $agg = [];
            foreach ($rows as $r) {
                $day = substr((string)$r->fecha, 0, 10);
                if (!isset($agg[$day])) $agg[$day] = ['sum'=>0,'cnt'=>0];
                if ($hasNumeric) {
                    $val = 0;
                    foreach ($valueKeys as $k) if (isset($r->$k) && is_numeric($r->$k)) { $val = $r->$k+0; break; }
                    $agg[$day]['sum'] += $val;
                    $agg[$day]['cnt'] += 1;
                } else {
                    // marcador 0/1 por día
                    $agg[$day]['sum'] = 1; $agg[$day]['cnt'] = 1;
                }
            }

            // Construir serie en orden
            for ($i=6; $i>=0; $i--) {
                $d = Carbon::now('America/Mexico_City')->subDays($i)->toDateString();
                if (!isset($agg[$d])) { $data[] = 0; continue; }
                $data[] = $hasNumeric
                    ? round($agg[$d]['sum'] / max(1,$agg[$d]['cnt']), 2)
                    : min(1, $agg[$d]['sum']);
            }

            return response()->json([
                'labels' => $labels,
                'data'   => $data,
                'mode'   => $hasNumeric ? 'avg' : 'count'
            ], 200);

        } catch (Exception $e) {
            return response()->json(['labels'=>[], 'data'=>[], 'mode'=>'count'], 200);
        }
    }

    // Listado simple de asignaciones personales (si no tienes relación, devuelve vacío sin romper UI)
    public function alumnoAsignaciones(Request $request)
    {
        try {
            $alumno = $request->user();
            [$personaStr, $personaObj] = $this->idBothTypes($alumno->persona_id ?? null);

            $items = [];

            if (class_exists(Actividad::class)) {
                $rows = Actividad::all()->filter(function($a) use ($personaStr){
                    // filtros defensivos por si guardas persona_id/matricula/cohorte
                    if (($a->alumno_id ?? null) === $personaStr) return true;
                    if (($a->persona_id ?? null) === $personaStr) return true;
                    return false;
                });

                foreach ($rows as $a) {
                    $items[] = [
                        'id'     => (string)($a->_id ?? $a->id ?? ''),
                        'tecnica'=> $a->tecnica ?? ($a->titulo ?? $a->nombre ?? 'Técnica/Actividad'),
                        'fecha'  => $a->fecha ?? ($a->fechaAsignacion ?? ($a->fecha_inicio ?? '')),
                        'estado' => $a->estado ?? 'pendiente',
                    ];
                }
            }

            return response()->json(['items'=>$items], 200);

        } catch (Exception $e) {
            return response()->json(['items'=>[]], 200);
        }
    }

    /* ============================================================
     |                      HELPERS PRIVADOS
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

    private function personaIdsByCohortesRegex(array $regexes): array
    {
        if (empty($regexes)) return [];
        $cursor = Persona::raw(function($c) use ($regexes) {
            return $c->find(['cohorte' => ['$in' => $regexes]], ['projection' => ['_id' => 1]]);
        });
        $ids = [];
        foreach ($cursor as $doc) $ids[] = (string) ($doc['_id'] ?? '');
        return array_values(array_filter($ids));
    }

    private function stringMatchesAnyRegex(string $value, array $regexes): bool
    {
        foreach ($regexes as $rx) {
            $plain = $this->stripAnchors($rx->getPattern());
            if (mb_strtoupper($value) === mb_strtoupper($plain)) return true;
        }
        foreach ($regexes as $rx) {
            $pat = '/' . $rx->getPattern() . '/i';
            if (@preg_match($pat, $value) === 1) return true;
        }
        return false;
    }

    private function stripAnchors(string $pattern): string
    {
        if (str_starts_with($pattern, '^')) $pattern = substr($pattern, 1);
        if (str_ends_with($pattern, '$'))   $pattern = substr($pattern, 0, -1);
        return $pattern;
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

    private function cohortesFromPersonaId($personaId): array
    {
        if (!$personaId) return [];
        $p = Persona::find($personaId);
        if (!$p) return [];
        $raw = $p->cohorte ?? null;
        if (is_array($raw)) return array_values(array_filter(array_map('strval',$raw)));
        if (is_string($raw) && trim($raw) !== '') return [trim($raw)];
        return [];
    }
}
