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

            // Conteo por citas (aceptando ambos tipos de docente_id)
            [$profStr, $profObj] = $this->idBothTypes($prof->id ?? $prof->_id ?? null);
            $alumnosUnicos = 0;

            if (($profStr || $profObj) && class_exists(Cita::class)) {
                $rows = Cita::where(function ($q) use ($profStr, $profObj) {
                            if ($profObj) $q->where('docente_id', $profObj);
                            if ($profStr) $q->orWhere('docente_id', $profStr);
                        })
                        ->get(['alumno_id']);
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
            [$profStr, $profObj] = $this->idBothTypes($prof->id ?? $prof->_id ?? null);
            [$cohortes, $regexes] = $this->resolveCohortesForUser($prof);

            // Bounds robustos (MX -> UTC)
            [$startStr, $endStr, $startUtc, $endUtc] = $this->monthBoundsUtc($request);

            $items = [];

            // ===== 1) Citas del profesor (robusto a String/ObjectId)
            if (($profStr || $profObj) && class_exists(Cita::class)) {
                $q = Cita::where(function ($w) use ($profStr, $profObj) {
                        if ($profObj) $w->where('docente_id', $profObj);
                        if ($profStr) $w->orWhere('docente_id', $profStr);
                        $w->orWhere('profesor_id', (string)$profStr)
                          ->orWhere('medico_id',   (string)$profStr)
                          ->orWhere('user_id',     (string)$profStr);
                    });

                // A) por DateTime
                $items = (clone $q)
                    ->where('fecha_cita', '>=', $startUtc)
                    ->where('fecha_cita', '<=', $endUtc)
                    ->get()
                    ->map(function ($c) {
                        $raw = $c->fecha_cita ?? null;
                        $iso = $raw instanceof \MongoDB\BSON\UTCDateTime
                            ? $raw->toDateTime()->format('c')
                            : (string)$raw;
                        return [
                            'titulo'      => (string)($c->motivo ?? 'Cita'),
                            'fecha'       => $iso,
                            'descripcion' => (string)($c->observaciones ?? ''),
                            'cohorte'     => null,
                        ];
                    })->values()->all();

                // B) fallback por string (YYYY-MM-DD)
                if (empty($items)) {
                    $items = (clone $q)
                        ->where('fecha_cita', '>=', $startStr)
                        ->where('fecha_cita', '<=', $endStr)
                        ->get()
                        ->map(function ($c) {
                            $raw = $c->fecha_cita ?? null;
                            $iso = $raw instanceof \MongoDB\BSON\UTCDateTime
                                ? $raw->toDateTime()->format('c')
                                : (string)$raw;
                            return [
                                'titulo'      => (string)($c->motivo ?? 'Cita'),
                                'fecha'       => $iso,
                                'descripcion' => (string)($c->observaciones ?? ''),
                                'cohorte'     => null,
                            ];
                        })->values()->all();
                }
            }

            // ===== 2) Fallback: actividades del profesor filtradas por cohorte y fechas
            if (empty($items) && class_exists(Actividad::class)) {
                $acts = Actividad::where(function ($q) use ($startStr, $endStr) {
                            $q->orWhereBetween('fecha',           [$startStr, $endStr])
                              ->orWhereBetween('fecha_inicio',    [$startStr, $endStr])
                              ->orWhereBetween('fechaFin',        [$startStr, $endStr])
                              ->orWhereBetween('fechaAsignacion', [$startStr, $endStr]);
                        })
                        ->get()
                        ->filter(function ($a) use ($regexes) {
                            $coh = (string) ($a->cohorte ?? $a->grupo ?? '');
                            if (!empty($regexes) && !$this->stringMatchesAnyRegex($coh, $regexes)) return false;
                            return true;
                        })
                        ->values();

                $items = $acts->map(function ($a) {
                            $fecha = $a->fecha
                                   ?? $a->fecha_inicio
                                   ?? $a->fechaFin
                                   ?? $a->fechaAsignacion
                                   ?? null;
                            return [
                                'titulo'      => $a->titulo ?? ($a->nombre ?? 'Actividad'),
                                'fecha'       => is_string($fecha) ? $fecha : (string)$fecha,
                                'descripcion' => $a->descripcion ?? ($a->detalle ?? ''),
                                'cohorte'     => $a->cohorte ?? ($a->grupo ?? null),
                            ];
                        })->all();
            }

            usort($items, fn($a, $b) => strtotime($a['fecha'] ?? '') <=> strtotime($b['fecha'] ?? ''));

            return response()->json([
                'items' => $items,
                'start' => $startStr,
                'end'   => $endStr,
            ], 200);

        } catch (Exception $e) {
            return response()->json(['items' => []], 200);
        }
    }

    public function profesorActividadesPorGrupo(Request $request)
    {
        try {
            $prof = $request->user();
            if (!$prof) return response()->json(['labels'=>[], 'data'=>[]], 200);

            // 1) Cohortes del profesor
            [$cohortes, $regexes] = $this->resolveCohortesForUser($prof);
            if (empty($cohortes)) {
                return response()->json(['labels'=>[], 'data'=>[]], 200);
            }

            // Normalización de labels del profe (preservamos orden para eje X)
            $norm = fn(string $s) => preg_replace('/\s+/', ' ', strtoupper(trim($s)));
            $profOrder = [];
            $profCanon = []; // norm => label original
            foreach ($cohortes as $lbl) {
                $n = $norm((string)$lbl);
                if ($n === '') continue;
                if (!isset($profCanon[$n])) { $profCanon[$n] = $lbl; $profOrder[] = $n; }
            }
            if (empty($profCanon)) return response()->json(['labels'=>[], 'data'=>[]], 200);

            // 2) Personas que pertenecen a las cohortes del profesor
            //    Traemos _id y cohorte para mapear a labels exactas del profe
            $personasCursor = Persona::raw(function($c) use ($regexes) {
                return $c->find(['cohorte' => ['$in' => $regexes]], ['projection' => ['_id'=>1,'cohorte'=>1]]);
            });

            // persona_id(string) => array de normLabels (intersección con cohortes del profe)
            $personaToNorms = [];
            foreach ($personasCursor as $doc) {
                $pid = $doc['_id'] ?? null;
                $pidStr = $pid instanceof ObjectId ? (string)$pid : (string)$pid;
                if ($pidStr === '') continue;

                $raw = $doc['cohorte'] ?? null;
                $vals = [];
                if (is_array($raw)) { foreach ($raw as $c) $vals[] = (string)$c; }
                elseif (is_string($raw)) { $vals[] = $raw; }

                $norms = [];
                foreach ($vals as $v) {
                    $nv = $norm($v);
                    if ($nv && isset($profCanon[$nv])) $norms[$nv] = true;
                }
                if (!empty($norms)) $personaToNorms[$pidStr] = array_keys($norms);
            }

            if (empty($personaToNorms)) {
                // No hay personas en esas cohortes → regresamos labels con 0 para que pinte eje X
                return response()->json(['labels'=>array_values($cohortes), 'data'=>array_fill(0, count($cohortes), 0)], 200);
            }

            // 3) Usuarios (estudiantes) cuya persona está en esas personas
            $personaIdsStr = array_keys($personaToNorms);
            $personaIdsObj = [];
            foreach ($personaIdsStr as $sid) {
                try { $personaIdsObj[] = new ObjectId($sid); } catch (\Throwable $e) {}
            }

            $users = User::whereIn('rol', ['estudiante','alumno'])
                ->where(function ($q) use ($personaIdsObj, $personaIdsStr) {
                    if (!empty($personaIdsObj)) $q->whereIn('persona_id', $personaIdsObj);
                    if (!empty($personaIdsStr)) $q->orWhereIn('persona_id', $personaIdsStr);
                })
                ->get(['_id','id','persona_id']);

            // user_id(string) => array de normLabels (desde su persona)
            $userToNorms = [];
            foreach ($users as $u) {
                $uid = (string)($u->_id ?? $u->id ?? '');
                if ($uid === '') continue;
                $pid = (string)($u->persona_id ?? '');
                if ($pid === '' || empty($personaToNorms[$pid])) continue;
                $userToNorms[$uid] = $personaToNorms[$pid]; // puede tener varias cohortes
            }

            // 4) Inicializa counters por las cohortes del profe
            $counts = array_fill_keys($profOrder, 0);

            // 5) ID del profesor (para atribuir actividades propias)
            [$profStr, $profObj] = $this->idBothTypes($prof->id ?? $prof->_id ?? null);

            // 6) Recorre actividades: si la actividad es del profe o tiene participantes de sus cohortes,
            //    suma 1 por cohorte involucrada (una vez por actividad y cohorte)
            if (class_exists(Actividad::class)) {
                foreach (Actividad::all() as $a) {
                    $belongsToProf = false;

                    // ¿Actividad creada por el profe?
                    $docenteId = (string)($a->docente_id ?? '');
                    if ($profStr && $docenteId && $docenteId === (string)$profStr) {
                        $belongsToProf = true;
                    } elseif ($profObj && $a->docente_id instanceof ObjectId && (string)$a->docente_id === (string)$profObj) {
                        $belongsToProf = true;
                    }

                    // Cohortes involucradas por participantes
                    $cohInAct = [];
                    $parts = $this->normalizeParticipantes($a->participantes ?? null);
                    foreach ($parts as $p) {
                        $uid = (string)($p['user_id'] ?? '');
                        if ($uid === '') continue;
                        if (!empty($userToNorms[$uid])) {
                            foreach ($userToNorms[$uid] as $n) $cohInAct[$n] = true;
                        }
                    }

                    // Si no es del profe y no hay participantes de sus cohortes, ignorar
                    if (!$belongsToProf && empty($cohInAct)) continue;

                    // Si es del profe pero no encontramos cohortes por participantes, intenta por etiqueta de actividad (cohorte/grupo)
                    if ($belongsToProf && empty($cohInAct)) {
                        $cohRaw = $a->cohorte ?? $a->grupo ?? null;
                        $vals = [];
                        if (is_array($cohRaw)) $vals = $cohRaw; elseif (is_string($cohRaw) && $cohRaw!=='') $vals[] = $cohRaw;
                        foreach ($vals as $v) {
                            $nv = $norm((string)$v);
                            if ($nv && isset($profCanon[$nv])) $cohInAct[$nv] = true;
                        }
                    }

                    // Sumar una vez por cohorte involucrada en esta actividad
                    if (!empty($cohInAct)) {
                        foreach (array_keys($cohInAct) as $n) {
                            if (array_key_exists($n, $counts)) {
                                $counts[$n] = ($counts[$n] ?? 0) + 1;
                            }
                        }
                    }
                }
            }

            // 7) Respuesta preservando orden y label “bonita” del profe
            $labels = [];
            $data   = [];
            foreach ($profOrder as $n) {
                $labels[] = $profCanon[$n];
                $data[]   = (int)($counts[$n] ?? 0);
            }

            return response()->json(['labels'=>$labels, 'data'=>$data], 200);

        } catch (Exception $e) {
            return response()->json(['labels'=>[], 'data'=>[]], 200);
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

    /**
     * Convierte el rango recibido (o el mes actual) de horario MX a UTC.
     * Devuelve: [$startStr, $endStr, $startUtc, $endUtc]
     */
    private function monthBoundsUtc(Request $request): array
    {
        $mxTz = 'America/Mexico_City';

        $startStr = $request->query('start') ?: Carbon::now($mxTz)->startOfMonth()->toDateString();
        $endStr   = $request->query('end')   ?: Carbon::now($mxTz)->endOfMonth()->toDateString();

        $startMx = Carbon::createFromFormat('Y-m-d H:i:s', $startStr.' 00:00:00', $mxTz);
        $endMx   = Carbon::createFromFormat('Y-m-d H:i:s', $endStr.' 23:59:59', $mxTz);

        $startUtc = $startMx->clone()->setTimezone('UTC');
        $endUtc   = $endMx->clone()->setTimezone('UTC');

        return [$startStr, $endStr, $startUtc, $endUtc];
    }

    /**
     * participantes puede ser array o string JSON; devuelve siempre array de objetos
     * con al menos la clave user_id (string).
     */
    private function normalizeParticipantes($p): array
    {
        if (is_array($p)) return $p;
        if (is_string($p) && $p !== '') {
            $dec = json_decode($p, true);
            return is_array($dec) ? $dec : [];
        }
        return [];
    }
}
