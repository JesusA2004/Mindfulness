<?php

namespace App\Http\Controllers\Api\Reportes;

use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Tecnica;
use App\Models\Persona;
use App\Models\User;

class TopTecnicasController extends BaseReportController
{
    /**
     * GET /api/reportes/top-tecnicas
     * Query params: desde, hasta, grupo (cohorte)
     * Response JSON:
     * {
     *   labels: [string],
     *   data:   [int],
     *   total:  int,
     *   rows:   [{ tecnica, total, porcentaje }]
     * }
     */
    public function index(Request $r)
    {
        $q = Actividad::query();
        $campoFecha = 'fechaMaxima';
        $q = $this->rangoFechas($r, $campoFecha, $q);

       // ===== Filtro por cohorte (acepta ?cohorte= o ?grupo=) =====
        $cohorteParam = $this->d($r, 'cohorte', $this->d($r, 'grupo'));
        if ($cohorteParam !== null && trim($cohorteParam) !== '') {
            $userIds = $this->userIdsByCohorte($cohorteParam);

            if (empty($userIds)) {
                return response()->json([
                    'labels' => [], 'data' => [], 'total' => 0, 'rows' => []
                ]);
            }

            // Mezcla segura (ObjectId + string)
            $mix = $this->mixedIn($userIds);

            // 👇 Match robusto: tu array es "participantes" con docs que pueden
            // tener user_id / usuario_id / userId según el histórico
            $q->where(function ($w) use ($mix) {
                $w->whereIn('participantes.user_id',   $mix)
                ->orWhereIn('participantes.usuario_id', $mix)
                ->orWhereIn('participantes.userId',  $mix);
            });
        }

        // Contar ocurrencias por técnica
        $rows = $q->get(['tecnica_id']);
        $conteo = [];
        foreach ($rows as $row) {
            $tid = (string)($row->tecnica_id ?? '');
            if ($tid === '') continue;
            $conteo[$tid] = ($conteo[$tid] ?? 0) + 1;
        }

        // Top 4
        $labels = []; $data = []; $tableRows = [];
        $totalUsos = array_sum($conteo);

        if (!empty($conteo)) {
            arsort($conteo);
            $top = array_slice($conteo, 0, 4, true);

            $tecs = Tecnica::whereIn('_id', array_keys($top))
                ->get(['_id','nombre'])->keyBy('_id');

            foreach ($top as $tid => $cnt) {
                $name = $this->s(optional($tecs->get($tid))->nombre) ?: 'Técnica';
                $pct  = $totalUsos > 0 ? round(($cnt / $totalUsos) * 100, 1) : 0.0;

                $labels[] = $name;
                $data[]   = (int)$cnt;
                $tableRows[] = [
                    'tecnica'    => $name,
                    'total'      => (int)$cnt,
                    'porcentaje' => $pct, // 0.0 – 100.0
                ];
            }
        }

        return response()->json([
            'labels' => $labels,
            'data'   => $data,
            'total'  => (int)$totalUsos,
            'rows'   => $tableRows,
        ]);
    }

    public function cohortes(Request $r)
    {
        try {
            $q = trim((string) $this->d($r, 'q', ''));

            // Helpers sin mbstring
            $toLower = static fn(string $s) => function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);

            // 1) Pipeline: toma 'cohorte' (string o array), aplana y devuelve valores únicos
            $cursor = \DB::connection('mongodb')
                ->getMongoDB()
                ->selectCollection('personas')
                ->aggregate([
                    // Solo docs donde exista el campo
                    ['$match' => ['cohorte' => ['$exists' => true]]],

                    // Normaliza a array: si es string lo convertimos a array de un solo elemento
                    ['$addFields' => [
                        'cohorteArr' => [
                            '$cond' => [
                                [ '$isArray' => '$cohorte' ],
                                '$cohorte',
                                [ '$cond' => [
                                    [ '$eq' => [ ['$type' => '$cohorte'], 'missing' ] ],
                                    [],              // missing -> []
                                    [ '$ifNull' => [ [ '$literal' => ['$cohorte'] ], [] ] ] // string/null -> [valor] o []
                                ]]
                            ]
                        ]
                    ]],

                    // Aplana
                    ['$unwind' => '$cohorteArr'],

                    // Limpia espacios
                    ['$project' => [
                        'c' => [
                            '$trim' => [ 'input' => ['$toString' => '$cohorteArr'] ]
                        ]
                    ]],

                    // Filtra vacíos
                    ['$match' => ['c' => ['$ne' => '']]],

                    // Únicos (case-insensitive): hacemos un key lower y agrupamos
                    ['$group' => [
                        '_id' => [ '$toLower' => '$c' ],
                        'any' => [ '$first' => '$c' ]   // conservamos una versión tal cual
                    ]],

                    // Sort natural insensible a mayúsculas (ordenamos por lower)
                    ['$sort' => ['_id' => 1]],

                    // Proyección final
                    ['$project' => ['_id' => 0, 'c' => '$any']]
                ]);

            $items = [];
            foreach ($cursor as $doc) {
                $val = isset($doc['c']) ? trim((string)$doc['c']) : '';
                if ($val !== '') $items[] = preg_replace('/\s+/u', ' ', $val);
            }

            // Filtro opcional por texto
            if ($q !== '') {
                $qL = $toLower($q);
                $items = array_values(array_filter($items, fn($c) => strpos($toLower($c), $qL) !== false));
            }

            // Orden natural final por si vienen tildes/mixto
            usort($items, fn($a, $b) => strnatcasecmp($a, $b));

            return response()->json(['items' => $items], 200);

        } catch (\Throwable $e) {
            \Log::error('Error /reportes/opciones/cohortes', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Error', 'error' => $e->getMessage()], 500);
        }
    }

}
