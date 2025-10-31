<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Exception;

// Modelos
use App\Models\User;
use App\Models\Actividad;
use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\Recompensa;
use App\Models\Tecnica;

class DashboardAlumnoController extends Controller
{
    /**
     * GET /api/dashboard/alumno/overview
     * KPIs: técnicas realizadas (Actividades con estado=Completado), citas pendientes del mes, recompensas obtenidas.
     */
    public function overview(Request $request)
    {
        try {
            $tz     = 'America/Mexico_City';
            $today  = Carbon::now($tz)->toDateString();
            $alumno = $request->user();

            if (!$alumno) {
                return response()->json([
                    'hoy'                 => $today,
                    'tecnicasRealizadas'  => 0,
                    'citasPendientesMes'  => 0,
                    'recompensas'         => 0,
                ], 200);
            }

            [$uid, $pid] = $this->userAndPersonaIds($alumno);

            // 1) Técnicas realizadas
            $tecnicasRealizadas = 0;
            if (class_exists(Actividad::class)) {
                $q = Actividad::query();
                $q->where(function ($w) use ($uid) {
                    $w->where('participantes', 'elemMatch', [
                        'user_id' => (string) $uid,
                        'estado'  => 'Completado',
                    ])->orWhere('participantes', 'regex',
                        new Regex('"user_id"\s*:\s*"' . preg_quote((string)$uid, '/') . '"[^}]*"estado"\s*:\s*"Completado"', 'i')
                    );
                });
                $tecnicasRealizadas = (int) $q->count();
            }

            // 2) Citas pendientes del mes
            $inicioMes = Carbon::now($tz)->startOfMonth()->format('Y-m-01\T00:00:00+00:00');
            $finMes    = Carbon::now($tz)->endOfMonth()->format('Y-m-t\T23:59:59+00:00');

            $citasPendientes = 0;
            if (class_exists(Cita::class)) {
                $estadosPend = ['Pendiente','Programada','Agendada','pending','por atender','Por atender'];
                $citasPendientes = (int) Cita::whereBetween('fecha_cita', [$inicioMes, $finMes])
                    ->where(function ($w) use ($uid, $pid) {
                        $w->where('alumno_id', (string)$uid)
                          ->orWhere('user_id',   (string)$uid)
                          ->orWhere('persona_id',(string)$pid)
                          ->orWhere('paciente_id',(string)$pid);
                    })
                    ->where(function ($w) use ($estadosPend) {
                        $w->whereIn('estado', $estadosPend)
                          ->orWhereNull('estado')
                          ->orWhere('estado', '');
                    })
                    ->count();
            }

            // 3) Recompensas obtenidas
            $recompensas = 0;
            if (class_exists(Recompensa::class)) {
                $ids = array_values(array_filter([(string)$uid, (string)$pid]));
                foreach (Recompensa::all() as $r) {
                    $raw = $r->canjeo ?? $r->canjeos ?? [];
                    if (is_string($raw)) {
                        $dec = json_decode(trim($raw), true);
                        $raw = is_array($dec) ? $dec : [];
                    }
                    if (!is_array($raw)) $raw = [];
                    foreach ($raw as $cj) {
                        $uids = [
                            (string)($cj['usuario_id'] ?? ''),
                            (string)($cj['alumno_id']  ?? ''),
                            (string)($cj['user_id']    ?? ''),
                            (string)($cj['persona_id'] ?? ''),
                        ];
                        if (count(array_intersect($ids, $uids)) > 0) {
                            $recompensas++;
                        }
                    }
                }
            }

            return response()->json([
                'hoy'                 => $today,
                'tecnicasRealizadas'  => $tecnicasRealizadas,
                'citasPendientesMes'  => $citasPendientes,
                'recompensas'         => $recompensas,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'hoy'                 => Carbon::now('America/Mexico_City')->toDateString(),
                'tecnicasRealizadas'  => 0,
                'citasPendientesMes'  => 0,
                'recompensas'         => 0,
            ], 200);
        }
    }

    /**
     * GET /api/dashboard/alumno/bienestar
     * Últimos 7 días: labels + data (conteo) + emotions (texto por día).
     */
    public function bienestarSemanal(Request $request)
    {
        try {
            $tz     = 'America/Mexico_City';
            $alumno = $request->user();
            if (!$alumno) return response()->json(['labels'=>[], 'data'=>[], 'emotions'=>[], 'predominante'=>null], 200);

            [$uid, $pid] = $this->userAndPersonaIds($alumno);

            $end   = Carbon::now($tz)->toDateString();
            $start = Carbon::now($tz)->subDays(6)->toDateString();

            // Eje X (7 días)
            $labelsDates = [];
            $labels = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = Carbon::now($tz)->subDays($i);
                $labelsDates[] = $d->toDateString();
                $labels[] = $this->weekdayShortEs((int)$d->dayOfWeekIso);
            }

            $data = array_fill(0, 7, 0);
            $freqEmocion = [];
            $byDayEmoCounts = array_fill(0, 7, []);   // conteo por emoción por día
            $byDayLastEmo   = array_fill(0, 7, null); // última emoción detectada en el día

            if (class_exists(Bitacora::class)) {
                $rows = Bitacora::whereBetween('fecha', [$start, $end])
                    ->orderBy('fecha','asc')
                    ->get();

                foreach ($rows as $b) {
                    // ¿Pertenece al alumno?
                    $cands = [
                        (string)($b->alumno_id ?? ''),
                        (string)($b->user_id   ?? ''),
                        (string)($b->persona_id?? ''),
                        (string)($b->estudiante_id ?? ''),
                    ];
                    $isMine = in_array((string)$uid, $cands, true) || in_array((string)$pid, $cands, true);
                    if (!$isMine) continue;

                    $f = (string)($b->fecha ?? '');
                    $dateKey = substr($f, 0, 10);
                    $pos = array_search($dateKey, $labelsDates, true);
                    if ($pos === false) continue;

                    // Conteo de entradas
                    $data[$pos] = (int)$data[$pos] + 1;

                    // === Emoción: extraer desde descripción / respuestas / regex ===
                    $emo = $this->extractEmotionFromBitacora($b);

                    if ($emo) {
                        $norm = $this->normalizeEmotionText($emo);
                        // global
                        $freqEmocion[$norm] = ($freqEmocion[$norm] ?? 0) + 1;
                        // por día (conteo y última)
                        $byDayEmoCounts[$pos][$norm] = ($byDayEmoCounts[$pos][$norm] ?? 0) + 1;
                        $byDayLastEmo[$pos] = $norm;
                    }
                }
            }

            // Predominante semanal
            arsort($freqEmocion);
            $predominante = !empty($freqEmocion) ? [
                'emocion' => array_key_first($freqEmocion),
                'conteo'  => array_values($freqEmocion)[0],
            ] : null;

            // Emoción por día (si hay varias: la de mayor frecuencia; si empatan, la última encontrada)
            $emotions = [];
            for ($i = 0; $i < 7; $i++) {
                if (empty($byDayEmoCounts[$i])) {
                    $emotions[] = null;
                    continue;
                }
                arsort($byDayEmoCounts[$i]); // mayor → menor
                $top = array_key_first($byDayEmoCounts[$i]);
                // si hubo empate y tenemos última detectada, preferimos esa
                $emotions[] = $top ?: $byDayLastEmo[$i];
            }

            return response()->json([
                'labels'       => $labels,
                'data'         => array_values($data),
                'emotions'     => $emotions,
                'predominante' => $predominante,
                'rango'        => ['start'=>$start, 'end'=>$end],
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'labels' => [],
                'data'   => [],
                'emotions' => [],
                'predominante' => null,
            ], 200);
        }
    }

    /**
     * GET /api/dashboard/alumno/asignaciones
     */
    public function asignaciones(Request $request)
    {
        try {
            $alumno = $request->user();
            if (!$alumno) return response()->json(['items'=>[]], 200);

            [$uid,] = $this->userAndPersonaIds($alumno);

            $q = Actividad::query();
            $q->where(function ($w) use ($uid) {
                $w->where('participantes', 'elemMatch', ['user_id' => (string)$uid])
                  ->orWhere('participantes', 'regex',
                    new Regex('"user_id"\s*:\s*"' . preg_quote((string)$uid, '/') . '"', 'i')
                  );
            });
            $q->orderBy('fechaAsignacion','desc')->orderBy('_id','desc');

            $acts = $q->limit(30)->get();

            // Pre-carga técnicas
            $tecnicaIds = [];
            foreach ($acts as $a) {
                $tid = (string)($a->tecnica_id ?? '');
                if ($tid) $tecnicaIds[$tid] = true;
            }
            $tecnicas = [];
            if (!empty($tecnicaIds)) {
                $oids = [];
                foreach (array_keys($tecnicaIds) as $tid) {
                    try { $oids[] = new ObjectId($tid); } catch (\Throwable $e) {}
                }
                if (!empty($oids)) {
                    $rows = Tecnica::whereIn('_id', $oids)->get(['_id','nombre','categoria']);
                    foreach ($rows as $t) $tecnicas[(string)$t->_id] = $t;
                }
            }

            $items = [];
            foreach ($acts as $a) {
                $part = $this->normalizeParticipants($a->participantes);
                $my   = null;
                foreach ($part as $p) {
                    if ((string)($p['user_id'] ?? '') === (string)$uid) { $my = $p; break; }
                }
                $estado = $my['estado'] ?? ($a->estado ?? 'Pendiente');

                $fecha = $a->fechaFinalizacion
                    ?? $a->fechaMaxima
                    ?? $a->fechaAsignacion
                    ?? null;

                $tid = (string)($a->tecnica_id ?? '');
                $tec = $tid && isset($tecnicas[$tid]) ? $tecnicas[$tid] : null;
                $tecName = $tec->nombre ?? ($a->nombre ?? 'Actividad');

                $items[] = [
                    'id'         => (string)($a->_id ?? $a->id),
                    'tecnica'    => $tecName,
                    'fecha'      => $fecha,
                    'estado'     => $estado,
                ];
            }

            return response()->json(['items' => array_slice($items, 0, 10)], 200);

        } catch (Exception $e) {
            return response()->json(['items' => []], 200);
        }
    }

    /* ========================= Helpers ========================= */

    private function userAndPersonaIds(User $u): array
    {
        $uid = (string)($u->_id ?? $u->id ?? '');
        $pid = (string)($u->persona_id ?? '');
        return [$uid, $pid];
    }

    private function normalizeParticipants($p): array
    {
        if (is_array($p)) return $p;
        if (is_string($p) && $p !== '') {
            $dec = json_decode($p, true);
            return is_array($dec) ? $dec : [];
        }
        return [];
    }

    private function weekdayShortEs(int $iso): string
    {
        $map = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];
        return $map[$iso] ?? '—';
    }

    /**
     * Extrae la emoción desde:
     * - $b->respuestas / $b->answers: elemento cuya pregunta contenga "emoción del día"
     * - $b->descripcion / $b->description: JSON (keys típicas) o texto (regex "Emoción del día:")
     * - Otros campos comunes (emocion, estado_emocional, etc.) como respaldo.
     */
    private function extractEmotionFromBitacora($b): ?string
    {
        // 1) respuestas/answers como arreglo
        $bags = [
            $b->respuestas  ?? null,
            $b->answers     ?? null,
            $b->res         ?? null,
        ];
        foreach ($bags as $arr) {
            $emo = $this->emotionFromAnswersBag($arr);
            if ($emo) return $emo;
        }

        // 2) descripcion/description como JSON o texto
        $descCands = [
            $b->descripcion ?? null,
            $b->description ?? null,
            $b->detalle     ?? null,
            $b->notes       ?? null,
        ];

        foreach ($descCands as $desc) {
            if (!isset($desc)) continue;

            // si es array/obj -> buscar claves
            if (is_array($desc)) {
                $emo = $this->emotionFromObjectLike($desc);
                if ($emo) return $emo;
            } elseif (is_object($desc)) {
                $emo = $this->emotionFromObjectLike((array)$desc);
                if ($emo) return $emo;
            } elseif (is_string($desc)) {
                // intentar JSON
                $trim = trim($desc);
                if ($trim !== '') {
                    $dec = json_decode($trim, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
                        $emo = $this->emotionFromObjectLike($dec);
                        if ($emo) return $emo;
                    }
                    // regex en texto plano: "Emoción del día: ..."
                    $emo = $this->emotionFromPlainText($trim);
                    if ($emo) return $emo;
                }
            }
        }

        // 3) Campos directos de respaldo
        $fallback = $this->extractEmotion($b);
        return $fallback ?: null;
    }

    /** Busca en arreglo de respuestas la pregunta "emoción del día" */
    private function emotionFromAnswersBag($arr): ?string
    {
        if (is_string($arr)) {
            $tmp = json_decode($arr, true);
            if (is_array($tmp)) $arr = $tmp;
        }
        if (!is_array($arr)) return null;

        foreach ($arr as $item) {
            if (is_string($item)) {
                // por si viniera string JSON dentro
                $j = json_decode($item, true);
                if (is_array($j)) $item = $j; else continue;
            }
            if (!is_array($item)) continue;

            $q = strtolower((string)($item['pregunta'] ?? $item['question'] ?? ''));
            if ($q === '') continue;

            if (strpos($q, 'emocion del dia') !== false || strpos($q, 'emoción del día') !== false) {
                $ans = $item['respuesta'] ?? $item['answer'] ?? $item['valor'] ?? null;
                $ans = is_string($ans) ? trim($ans) : (is_scalar($ans) ? (string)$ans : null);
                if ($ans !== null && $ans !== '') return $ans;
            }
        }
        return null;
    }

    /** Busca claves típicas dentro de objeto/array (decodificado de JSON) */
    private function emotionFromObjectLike(array $obj): ?string
    {
        // claves candidatas
        $keys = [
            'emocion','emoción','emocion_del_dia','emoción_del_día',
            'emocionDia','emocion_dia','emotional_state','estado_emocional',
            'mood','sentimiento','emocionDelDia'
        ];
        foreach ($keys as $k) {
            if (!array_key_exists($k, $obj)) continue;
            $val = $obj[$k];
            if (is_string($val)) { $t = trim($val); if ($t !== '') return $t; }
            if (is_scalar($val))  { $t = trim((string)$val); if ($t !== '') return $t; }
        }

        // También puede venir como lista de QA
        $qaKeys = ['respuestas','answers','preguntas'];
        foreach ($qaKeys as $qa) {
            if (isset($obj[$qa])) {
                $emo = $this->emotionFromAnswersBag($obj[$qa]);
                if ($emo) return $emo;
            }
        }
        return null;
    }

    /** Extrae con regex desde texto plano "Emoción del día: XXX" */
    private function emotionFromPlainText(string $txt): ?string
    {
        // admite acento/without acento y separadores (dos puntos, guión, etc.)
        $pattern = '/emoci[oó]n\s+del\s+d[ií]a\s*[:\-–]\s*([^\r\n;|]+)/iu';
        if (preg_match($pattern, $txt, $m)) {
            $emo = trim($m[1] ?? '');
            if ($emo !== '') return $emo;
        }
        return null;
    }

    /** Normaliza capitalización: "feliz" → "Feliz" */
    private function normalizeEmotionText(string $s): string
    {
        $s = trim($s);
        if ($s === '') return $s;
        // lower + ucfirst multibyte
        $lower = mb_strtolower($s, 'UTF-8');
        $first = mb_strtoupper(mb_substr($lower, 0, 1, 'UTF-8'), 'UTF-8');
        return $first . mb_substr($lower, 1, null, 'UTF-8');
    }

    /** Respaldo clásico */
    private function extractEmotion($b): ?string
    {
        $cands = [
            $b->emocion           ?? null,
            $b->estado_emocional  ?? null,
            $b->estado            ?? null,
            $b->mood              ?? null,
            $b->sentimiento       ?? null,
        ];
        foreach ($cands as $v) {
            $s = trim((string)$v);
            if ($s !== '') return $this->normalizeEmotionText($s);
        }
        return null;
    }
}
