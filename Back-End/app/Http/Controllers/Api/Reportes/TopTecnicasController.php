<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Tecnica;

class TopTecnicasController extends BaseReportController
{
    /**
     * GET /api/reportes/top-tecnicas
     * Query params: desde, hasta, grupo (cohorte)
     */
    public function index(Request $r)
    {
        // ====== Filtros ======
        $desde        = $this->d($r, 'desde');
        $hasta        = $this->d($r, 'hasta');
        $cohorteParam = $this->d($r, 'cohorte', $this->d($r, 'grupo'));
        $needCohorte  = $cohorteParam !== null && trim($cohorteParam) !== '';

        // Normalizador de cohortes (case + espacios)
        $compact = static function (string $s): string {
            $s = preg_replace('/\s+/u', ' ', trim($s));
            if ($s === null) $s = '';
            return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
        };
        $targetNorm = $needCohorte ? $compact($cohorteParam) : null;

        // ====== Query base ======
        $acts = Actividad::query()
            ->select(['_id','tecnica_id','fechaAsignacion','fechaMaxima','participantes'])
            ->get();

        // ====== Funciones fecha ======
        $inRange = function (?string $fa, ?string $fm) use ($desde,$hasta): bool {
            $ok = function (?string $s) use ($desde,$hasta): bool {
                if (!$s || $s==='') return false;
                try {
                    $d = \Carbon\Carbon::parse($s);
                    if ($desde && $d->lt(\Carbon\Carbon::parse($desde)->startOfDay())) return false;
                    if ($hasta && $d->gt(\Carbon\Carbon::parse($hasta)->endOfDay())) return false;
                    return true;
                } catch (\Throwable $e) { return false; }
            };
            if (!$desde && !$hasta) return true;
            return $ok($fa) || $ok($fm);
        };

        // ====== Pre-scan: juntar TODOS los user_id que aparecen (para construir el mapa cohorte) ======
        $allUidsInRange = [];
        foreach ($acts as $a) {
            $fa = $this->s($a->fechaAsignacion ?? '');
            $fm = $this->s($a->fechaMaxima ?? '');
            if (!$inRange($fa, $fm)) continue;

            $parts = $a->participantes;
            if (is_string($parts)) {
                $dec = json_decode($parts, true);
                $parts = is_array($dec) ? $dec : [];
            } elseif (!is_array($parts)) {
                $parts = [];
            }
            foreach ($parts as $p) {
                $uid = $p['user_id'] ?? ($p['usuario_id'] ?? ($p['userId'] ?? null));
                if ($uid === null || $uid==='') continue;
                $allUidsInRange[] = (string)$uid;
            }
        }
        $allUidsInRange = array_values(array_unique($allUidsInRange));

        // ====== Mapa user_id -> [cohortes] (combina ambas rutas) ======
        $userToCohortes = [];
        if (!empty($allUidsInRange)) {
            $db = \DB::connection('mongodb')->getMongoDB();

            // 1) Personas por user_id (cuando exista personas.user_id)
            $cursor1 = $db->selectCollection('personas')->aggregate([
                ['$match' => [ 'user_id' => ['$in' => $this->mixedIn($allUidsInRange)] ]],
                ['$project' => ['user_id' => 1, 'cohorte' => 1]]
            ]);
            foreach ($cursor1 as $doc) {
                $uid = isset($doc['user_id']) ? (string)$doc['user_id'] : null;
                if (!$uid) continue;
                $arr = [];
                $c = $doc['cohorte'] ?? null;
                if (is_array($c)) $arr = $c;
                elseif ($c !== null && $c !== '') $arr = [$c];
                foreach ($arr as $item) {
                    $val = preg_replace('/\s+/u', ' ', trim((string)$item));
                    if ($val === '') continue;
                    $userToCohortes[$uid][] = $val;
                }
            }

            // 2) Fallback: Users (_id -> persona_id) -> Personas (_id -> cohorte)
            // Users de los uids que no quedaron cubiertos en (1) o para mergear datos
            $users = \App\Models\User::whereIn('_id', $this->mixedIn($allUidsInRange))
                ->get(['_id','persona_id']);
            $personaIds = $users->pluck('persona_id')->filter()->unique()->values()->all();

            if (!empty($personaIds)) {
                $pers = \App\Models\Persona::whereIn('_id', $this->mixedIn($personaIds))
                    ->get(['_id','cohorte'])->keyBy('_id');

                foreach ($users as $u) {
                    $uid = (string)$u->_id;
                    $pid = (string)($u->persona_id ?? '');
                    if ($pid === '' || !isset($pers[$pid])) continue;

                    $c = $pers[$pid]->cohorte ?? null;
                    $arr = [];
                    if (is_array($c)) $arr = $c;
                    elseif ($c !== null && $c !== '') $arr = [$c];

                    foreach ($arr as $item) {
                        $val = preg_replace('/\s+/u', ' ', trim((string)$item));
                        if ($val === '') continue;
                        $userToCohortes[$uid][] = $val;
                    }
                }
            }

            // Dejar únicos y normalizados (pero conservando texto original para mostrar)
            foreach ($userToCohortes as $uid => $list) {
                $userToCohortes[$uid] = array_values(array_unique(array_map(fn($x)=> (string)$x, $list)));
            }
        }

        // ====== Conteo final (aplica filtro por cohorte usando el mapa) ======
        $conteo           = []; // tecnicaId => usos
        $fechasPorTecnica = []; // tecnicaId => [fechas]
        $totalUsos        = 0;
        $countedUserIds   = [];

        foreach ($acts as $a) {
            $tid = (string)($a->tecnica_id ?? '');
            if ($tid === '') continue;

            $fa = $this->s($a->fechaAsignacion ?? '');
            $fm = $this->s($a->fechaMaxima ?? '');
            if (!$inRange($fa, $fm)) continue;

            $parts = $a->participantes;
            if (is_string($parts)) {
                $dec = json_decode($parts, true);
                $parts = is_array($dec) ? $dec : [];
            } elseif (!is_array($parts)) {
                $parts = [];
            }

            foreach ($parts as $p) {
                $uid = $p['user_id'] ?? ($p['usuario_id'] ?? ($p['userId'] ?? null));
                if ($uid === null || $uid==='') continue;
                $uid = (string)$uid;

                if ($needCohorte) {
                    $has = false;
                    $list = $userToCohortes[$uid] ?? [];
                    foreach ($list as $co) {
                        if ($compact($co) === $targetNorm) { $has = true; break; }
                    }
                    if (!$has) continue; // no pertenece al cohorte filtrado
                }

                $fUso = $fa ?: $fm ?: null;

                $conteo[$tid] = ($conteo[$tid] ?? 0) + 1;
                if (!isset($fechasPorTecnica[$tid])) $fechasPorTecnica[$tid] = [];
                if ($fUso) $fechasPorTecnica[$tid][] = $fUso;

                $totalUsos++;
                $countedUserIds[] = $uid;
            }
        }

        if ($totalUsos === 0) {
            return response()->json([
                'labels' => [], 'data' => [], 'total' => 0, 'rows' => [], 'usageDates' => [],
                'meta'   => [
                    'rango'     => ($desde && $hasta) ? ($desde.' → '.$hasta) : 'Todas las fechas',
                    'cohorte'   => $needCohorte ? $cohorteParam : null,
                    'cohortes'  => [],
                ],
            ]);
        }

        // ====== Top-4 ======
        arsort($conteo);
        $top = array_slice($conteo, 0, 4, true);

        // Resolver nombres (acepta ids como ObjectId|string)
        $idKeys  = array_keys($top);
        $tecsMap = Tecnica::whereIn('_id', $this->mixedIn($idKeys))
            ->get(['_id','nombre'])
            ->reduce(function(array $acc, $t){
                $acc[(string)$t->_id] = (string)($t->nombre ?? '');
                return $acc;
            }, []);

        // Empaquetado
        $labels     = [];
        $data       = [];
        $tableRows  = [];
        $usageDates = [];

        foreach ($top as $tid => $cnt) {
            $name = $tecsMap[$tid] ?? ( $this->isOid($tid) ? '' : $tid );
            if ($name === '') $name = 'Técnica';

            $pct = $totalUsos > 0 ? round(($cnt / $totalUsos) * 100, 1) : 0.0;

            $labels[] = $name;
            $data[]   = (int)$cnt;
            $tableRows[] = [
                'tecnica'    => $name,
                'total'      => (int)$cnt,
                'porcentaje' => $pct,
            ];

            $dd = array_values(array_unique(array_map(function($s){
                try { return \Carbon\Carbon::parse($s)->format('Y-m-d'); } catch (\Throwable $e) { return (string)$s; }
            }, $fechasPorTecnica[$tid] ?? [])));
            sort($dd, SORT_NATURAL);
            $usageDates[] = $dd;
        }

        // ====== Cohortes involucrados (de los usuarios contados) ======
        $cohortesInvolucrados = [];
        foreach (array_values(array_unique($countedUserIds)) as $uid) {
            foreach ($userToCohortes[$uid] ?? [] as $c) {
                $v = preg_replace('/\s+/u', ' ', trim((string)$c));
                if ($v !== '') $cohortesInvolucrados[] = $v;
            }
        }
        $cohortesInvolucrados = array_values(array_unique($cohortesInvolucrados));

        return response()->json([
            'labels'     => $labels,
            'data'       => $data,
            'total'      => (int)$totalUsos,
            'rows'       => $tableRows,
            'usageDates' => $usageDates,
            'meta'       => [
                'rango'     => ($desde && $hasta) ? ($desde.' → '.$hasta) : 'Todas las fechas',
                'cohorte'   => $needCohorte ? $cohorteParam : null,
                'cohortes'  => $cohortesInvolucrados,
            ],
        ]);
    }

    /** Autocomplete de cohortes: igual que ya tenías */
    public function cohortes(Request $r)
    {
        try {
            $q = trim((string) $this->d($r, 'q', ''));
            $toLower = static fn(string $s) => function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);

            $cursor = \DB::connection('mongodb')->getMongoDB()
                ->selectCollection('personas')->aggregate([
                    ['$match' => ['cohorte' => ['$exists' => true]]],
                    ['$addFields' => [
                        'cohorteArr' => [
                            '$cond' => [
                                [ '$isArray' => '$cohorte' ],
                                '$cohorte',
                                [ '$cond' => [
                                    [ '$eq' => [ ['$type' => '$cohorte'], 'missing' ] ],
                                    [],
                                    [ '$ifNull' => [ [ '$literal' => ['$cohorte'] ], [] ] ]
                                ]]
                            ]
                        ]
                    ]],
                    ['$unwind' => '$cohorteArr'],
                    ['$project' => [ 'c' => [ '$trim' => [ 'input' => [ '$toString' => '$cohorteArr' ] ] ] ]],
                    ['$match' => ['c' => ['$ne' => '']]],
                    ['$group' => [ '_id' => [ '$toLower' => '$c' ], 'any' => [ '$first' => '$c' ] ]],
                    ['$sort'  => ['_id' => 1]],
                    ['$project' => ['_id' => 0, 'c' => '$any']]
                ]);

            $items = [];
            foreach ($cursor as $doc) {
                $val = isset($doc['c']) ? trim((string)$doc['c']) : '';
                if ($val !== '') $items[] = preg_replace('/\s+/u', ' ', $val);
            }

            if ($q !== '') {
                $qL = $toLower($q);
                $items = array_values(array_filter($items, fn($c) => strpos($toLower($c), $qL) !== false));
            }

            usort($items, fn($a, $b) => strnatcasecmp($a, $b));
            return response()->json(['items' => $items], 200);

        } catch (\Throwable $e) {
            \Log::error('Error /reportes/opciones/cohortes', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }
}
