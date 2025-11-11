<?php

namespace App\Http\Controllers\Api\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use MongoDB\BSON\ObjectId;

// MODELOS
use App\Models\Tecnica;
use App\Models\Actividad;
use App\Models\Cita;
use App\Models\Bitacora;
use App\Models\Encuesta;
use App\Models\Recompensa;
use App\Models\Persona;
use App\Models\User;

abstract class BaseReportController extends Controller
{
    protected function isOid($v): bool {
        return is_string($v) && preg_match('/^[a-f0-9]{24}$/i', $v);
    }

    protected function d(Request $r, string $key, $default = null) {
        $v = trim((string) $r->query($key, ''));
        return $v === '' ? $default : $v;
    }

    /** ObjectId[] desde strings (descarta inválidos) */
    protected function asObjectIds(array $ids): array {
        $out = [];
        foreach ($ids as $id) {
            $s = (string)$id;
            if ($this->isOid($s)) {
                try { $out[] = new ObjectId($s); } catch (\Throwable $e) {}
            }
        }
        return $out;
    }

    /** Mezcla para $in: ObjectId[] + strings (sin duplicar) */
    protected function mixedIn(array $ids): array {
        $oids = $this->asObjectIds($ids);
        $seen = [];
        $mix  = [];
        foreach ($oids as $o) { $k=(string)$o; if(!isset($seen[$k])){$mix[]=$o;$seen[$k]=1;} }
        foreach ($ids  as $s) { $k=(string)$s; if(!isset($seen[$k])){$mix[]=$k;$seen[$k]=1;} }
        return $mix;
    }

    protected function rangoFechas(Request $r, string $campoFecha, $query)
    {
        $desde = $this->d($r, 'desde');
        $hasta = $this->d($r, 'hasta');

        if ($desde) {
            try { $query = $query->where($campoFecha, '>=', Carbon::parse($desde)->startOfDay()); } catch (\Throwable $e) {}
        }
        if ($hasta) {
            try { $query = $query->where($campoFecha, '<=', Carbon::parse($hasta)->endOfDay()); } catch (\Throwable $e) {}
        }
        return $query;
    }

    protected function s($v) { return is_string($v) ? $v : (is_null($v) ? '' : (string)$v); }

    protected function extractMatricula(?string $s): ?string {
        if (!$s) return null;
        if (strpos($s, '—') !== false) {
            $parts = array_map('trim', explode('—', $s));
            $mat = end($parts);
            return $mat !== '' ? $mat : null;
        }
        return null;
    }

    protected function normalizeAlumnoNeedle(?string $needle): array {
        $needle = trim((string)$needle);
        if ($needle === '') return ['mat' => null, 'tokens' => []];

        if ($mat = $this->extractMatricula($needle)) return ['mat' => $mat, 'tokens' => []];
        if (preg_match('/[A-Z0-9\-]{5,}/i', $needle)) return ['mat' => $needle, 'tokens' => []];

        $tokens = array_values(array_filter(array_map('trim', preg_split('/\s+/', $needle))));
        return ['mat' => null, 'tokens' => $tokens];
    }

    /** ===== Mapeos Users <-> Personas ===== */
    protected function personaIdsBySearch(?string $needle): array
    {
        $norm = $this->normalizeAlumnoNeedle($needle);

        return Persona::query()
            ->when($norm['mat'] !== null, function ($q) use ($norm) {
                $q->where('matricula', 'like', '%'.$norm['mat'].'%');
            })
            ->when($norm['mat'] === null && !empty($norm['tokens']), function ($q) use ($norm) {
                $q->where(function ($w) use ($norm) {
                    foreach ($norm['tokens'] as $t) {
                        $w->orWhere('nombre', 'like', "%{$t}%")
                          ->orWhere('apellidoPaterno', 'like', "%{$t}%")
                          ->orWhere('apellidoMaterno', 'like', "%{$t}%")
                          ->orWhere('cohorte', 'like', "%{$t}%");
                    }
                });
            })
            ->limit(500)
            ->pluck('_id')
            ->map(fn($id) => (string)$id)
            ->values()
            ->all();
    }

    protected function userIdsByAlumnoSearch(?string $needle): array
    {
        $personaIds = $this->personaIdsBySearch($needle);
        if (empty($personaIds)) return [];
        return User::whereIn('persona_id', $this->mixedIn($personaIds))
            ->pluck('_id')->map(fn($id)=>(string)$id)->values()->all();
    }

    protected function userIdsByCohorte(string $cohorte): array
    {
        $target = trim($cohorte);
        if ($target === '') return [];

        // Normaliza para match insensible a espacios múltiples y mayúsculas
        $compact = static function (string $s): string {
            $s = preg_replace('/\s+/u', ' ', trim($s));
            if ($s === null) $s = '';
            return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
        };
        $targetNorm = $compact($target);

        $db   = \DB::connection('mongodb')->getMongoDB();
        $coll = $db->selectCollection('personas');

        // 1) Primer intento: personas con user_id
        $cursor = $coll->aggregate([
            ['$match' => [ 'cohorte' => ['$exists' => true], 'user_id' => ['$exists' => true] ]],
            ['$addFields' => [
                'cohorteArr' => [
                    '$cond' => [
                        [ '$isArray' => '$cohorte' ],
                        '$cohorte',
                        [
                            '$cond' => [
                                [ '$eq' => [ ['$type' => '$cohorte'], 'string' ] ],
                                [ '$cohorte' ],
                                []
                            ]
                        ]
                    ]
                ]
            ]],
            ['$unwind' => '$cohorteArr'],
            ['$addFields' => [
                'c_norm' => [
                    '$toLower' => [
                        '$reduce' => [
                            'input' => [
                                '$filter' => [
                                    'input' => [ '$split' => [ [ '$trim' => [ 'input' => [ '$toString' => '$cohorteArr' ] ] ], ' ' ] ],
                                    'as'    => 't',
                                    'cond'  => [ '$ne' => [ '$$t', '' ] ]
                                ]
                            ],
                            'initialValue'=> '',
                            'in' => [
                                '$cond' => [
                                    [ '$eq' => [ '$$value', '' ] ], '$$this',
                                    [ '$concat' => [ '$$value', ' ', '$$this' ] ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]],
            ['$match' => [ 'c_norm' => $targetNorm ]],
            ['$project' => [ '_id' => 0, 'uid' => '$user_id' ]],
        ]);

        $uidSet = [];
        foreach ($cursor as $doc) {
            $v = $doc['uid'] ?? null;
            if ($v === null) continue;
            $uidSet[] = is_object($v) && method_exists($v, '__toString') ? (string)$v : (string)$v;
        }
        $uidSet = array_values(array_unique(array_filter($uidSet)));

        if (!empty($uidSet)) return $uidSet;

        // 2) Fallback: personas por cohorte -> users por persona_id
        $cursor2 = $coll->aggregate([
            ['$match' => [ 'cohorte' => ['$exists' => true] ]],
            ['$addFields' => [
                'cohorteArr' => [
                    '$cond' => [
                        [ '$isArray' => '$cohorte' ],
                        '$cohorte',
                        [
                            '$cond' => [
                                [ '$eq' => [ ['$type' => '$cohorte'], 'string' ] ],
                                [ '$cohorte' ],
                                []
                            ]
                        ]
                    ]
                ]
            ]],
            ['$unwind' => '$cohorteArr'],
            ['$addFields' => [
                'c_norm' => [
                    '$toLower' => [
                        '$reduce' => [
                            'input' => [
                                '$filter' => [
                                    'input' => [ '$split' => [ [ '$trim' => [ 'input' => [ '$toString' => '$cohorteArr' ] ] ], ' ' ] ],
                                    'as'    => 't',
                                    'cond'  => [ '$ne' => [ '$$t', '' ] ]
                                ]
                            ],
                            'initialValue'=> '',
                            'in' => [
                                '$cond' => [
                                    [ '$eq' => [ '$$value', '' ] ], '$$this',
                                    [ '$concat' => [ '$$value', ' ', '$$this' ] ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]],
            ['$match' => [ 'c_norm' => $targetNorm ]],
            ['$project' => [ '_id' => 1 ]],
        ]);

        $personaIds = [];
        foreach ($cursor2 as $doc) {
            $pid = $doc['_id'] ?? null;
            if ($pid === null) continue;
            $personaIds[] = (string)$pid;
        }
        if (empty($personaIds)) return [];

        $userIds = \App\Models\User::whereIn('persona_id', $this->mixedIn($personaIds))
            ->pluck('_id')->map(fn($id)=>(string)$id)->values()->all();

        return array_values(array_unique($userIds));
    }

    protected function personaMapByUserIds(array $userIds)
    {
        if (empty($userIds)) return collect();

        $users = User::whereIn('_id', $this->mixedIn($userIds))
            ->get(['_id','persona_id']);

        $personaIds = $users->pluck('persona_id')->filter()->unique()->values()->all();
        if (empty($personaIds)) return collect();

        $personas = Persona::whereIn('_id', $this->mixedIn($personaIds))
            ->get(['_id','nombre','apellidoPaterno','apellidoMaterno','matricula'])
            ->keyBy('_id');

        $map = [];
        foreach ($users as $u) {
            $pid = (string)($u->persona_id ?? '');
            $map[(string)$u->_id] = $pid && isset($personas[$pid]) ? $personas[$pid] : null;
        }
        return collect($map);
    }

    /** ===== Utilidades export ===== */
    protected function rowsFromChart(array $payload, string $k1, string $k2): array
    {
        $labels = $payload['labels'] ?? [];
        $data   = $payload['data'] ?? ($payload['datasets'][0]['data'] ?? []);

        $rows = [];
        foreach ($labels as $i => $label) {
            $rows[] = [
                $k1 => is_string($label) ? $label : (string)$label,
                $k2 => (int)($data[$i] ?? 0),
            ];
        }
        return $rows;
    }

    protected function arrayToCsv(array $rows): string
    {
        if (empty($rows)) return "Mensaje\nSin datos\n";
        $cols = array_keys((array)$rows[0]);
        $out  = fopen('php://temp', 'r+');
        fputcsv($out, $cols);
        foreach ($rows as $r) {
            $line = [];
            foreach ($cols as $c) $line[] = $r[$c] ?? '';
            fputcsv($out, $line);
        }
        rewind($out);
        return stream_get_contents($out);
    }

    protected function simpleTableHtml(string $title, array $rows, array $meta = []): string
    {
        $cols = !empty($rows) ? array_keys((array)$rows[0]) : ['Mensaje'];
        if (empty($rows)) $rows = [['Mensaje' => 'Sin datos']];

        // Chips (rango + filtros)
        $chips = '';
        $rango = $meta['rango'] ?? 'Todas las fechas';
        $chips .= '<span class="chip"><b>Rango:</b> '.e($rango).'</span>';
        foreach ($meta as $k => $v) {
            if (in_array($k, ['rango','chartData','chartType'], true)) continue;
            $chips .= '<span class="chip"><b>'.e($k).':</b> '.e($v).'</span>';
        }

        // Encabezados
        $head = '';
        foreach ($cols as $c) $head .= '<th>'.e($c).'</th>';

        // Filas
        $body = '';
        $i = 0;
        foreach ($rows as $r) {
            $i++;
            $cells = '';
            foreach ($cols as $c) $cells .= '<td>'.e($r[$c] ?? '').'</td>';
            $body .= '<tr class="'.($i%2? 'odd':'even').'">'.$cells.'</tr>';
        }

        // ===== Gráfica (opcional) =====
        $chartHtml = '';
        $chartData = $meta['chartData'] ?? [];
        $chartType = strtolower((string)($meta['chartType'] ?? 'vbar')); // vbar|hbar (default vbar)

        if (!empty($chartData) && is_array($chartData)) {
            if ($chartType === 'vbar') {
                // columnas verticales (PDF-safe: sin flexbox)
                $colsHtml = '';
                foreach ($chartData as $b) {
                    $lbl = e((string)($b['label'] ?? ''));
                    $pct = max(0, min(100, (float)($b['pct'] ?? 0)));
                    $val = (int)($b['value'] ?? 0);

                    $datesList = '';
                    if (!empty($b['dates']) && is_array($b['dates'])) {
                        $items = array_slice($b['dates'], 0, 6); // acota
                        $li = '';
                        foreach ($items as $it) $li .= '<li>'.e((string)$it).'</li>';
                        $datesList = '<ul class="dates">'.$li.'</ul>';
                    }

                    $colsHtml .= '
                    <div class="col">
                    <div class="val">'.$val.' ('.number_format($pct,1).'%)</div>
                    <div class="bar"><span class="fill" style="height:'.$pct.'%"></span></div>
                    <div class="lbl">'.$lbl.'</div>
                    '.$datesList.'
                    </div>';
                }
                $chartHtml = '<div class="chart-v"><div class="grid">'.$colsHtml.'</div></div>';
            } else {
                // fallback barras horizontales
                $bars = '';
                foreach ($chartData as $b) {
                    $lbl = e((string)($b['label'] ?? ''));
                    $pct = max(0, min(100, (float)($b['pct'] ?? 0)));
                    $val = (int)($b['value'] ?? 0);
                    $bars .= '<div class="bar-h">
                                <div class="lbl">'.$lbl.'</div>
                                <div class="track"><div class="fill" style="width:'.$pct.'%"></div></div>
                                <div class="val">'.$val.' ('.number_format($pct,1).'%)</div>
                              </div>';
                }
                $chartHtml = '<div class="chart-h">'.$bars.'</div>';
            }
        }

        return '<!DOCTYPE html><html lang="es"><meta charset="utf-8">
    <title>'.e($title).'</title>
    <style>
    *{ box-sizing:border-box; }
    body{ font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; color:#0f172a; margin:24px; }
    .header{ padding:16px 18px; border:1px solid #e5e7eb; border-radius:14px; background:linear-gradient(180deg,#fafbff,#ffffff); }
    h1{ margin:0 0 2px 0; font-size:20px; }
    .sub{ color:#64748b; margin:0 0 10px 0; font-size:12px; }
    .chips{ display:flex; gap:8px; flex-wrap:wrap; }
    .chip{ display:inline-flex; gap:6px; align-items:center; padding:6px 10px; border:1px solid #e5e7eb; border-radius:999px; background:#fff; font-size:12px; }

    /* Tabla */
    table{ width:100%; border-collapse:collapse; margin-top:14px; border:1px solid #e5e7eb; }
    thead th{ text-align:left; padding:8px; font-size:12px; background:#f7f8fc; border-bottom:1px solid #e5e7eb; }
    tbody td{ padding:8px; font-size:12px; border-bottom:1px solid #f1f5f9; }
    tbody tr.even{ background:#fbfbff; }
    footer{ margin-top:8px; color:#94a3b8; font-size:11px; }

    /* ===== Gráfica vertical (PDF-safe, sin flexbox) ===== */
    .chart-v{ margin:16px 0 6px 0; padding:16px; border:1px solid #e5e7eb; border-radius:14px; background:linear-gradient(180deg,#fcfdff,#ffffff); }
    .chart-v .grid{
    text-align:center;
    white-space:nowrap;
    padding:8px 6px 12px 6px;
    border-bottom:1px solid #e5e7eb;
    font-size:0; /* elimina espacios entre inline-blocks */
    }
    .chart-v .col{
    display:inline-block;
    vertical-align:bottom;
    width:22%;
    margin:0 1.5%;
    font-size:12px; /* reestablece */
    }
    .chart-v .bar{
        width:26px; height:160px;
        background:#ede9fe; border:1px solid #e5e7eb;
        border-radius:6px 6px 0 0; overflow:hidden; margin:0 auto;
        position:relative;           /* <-- agregado */
    }
    .chart-v .fill{
        display:block; width:100%;
        position:absolute; bottom:0; /* <-- agregado: anclar abajo */
        height:0;                    /* se sobreescribe via style="height:X%" */
        background:#7c3aed;
    }
    .chart-v .val{ font-size:11px; color:#334155; font-weight:700; margin-bottom:6px; line-height:1.1; }
    .chart-v .lbl{ font-size:12px; color:#0f172a; font-weight:700; text-align:center; margin-top:8px; line-height:1.2; }
    .chart-v .dates{ list-style:none; padding:0; margin:6px 0 0 0; font-size:11px; color:#475569; }

    /* (opcional) barras horizontales fallback */
    .chart-h{ margin:16px 0 6px 0; padding:16px; border:1px solid #e5e7eb; border-radius:14px; background:linear-gradient(180deg,#fcfdff,#ffffff);}
    .chart-h .bar-h{ display:flex; align-items:center; gap:10px; margin:8px 0; }
    .chart-h .bar-h .lbl{ width:32%; font-size:12px; font-weight:700; color:#0f172a; }
    .chart-h .bar-h .track{ flex:1; height:14px; background:#eef2ff; border-radius:999px; overflow:hidden; border:1px solid #e5e7eb; }
    .chart-h .bar-h .fill{ height:100%; background:#7c3aed; }
    .chart-h .bar-h .val{ width:16%; text-align:right; font-size:12px; color:#334155; font-weight:700; }
    </style>
    <body>
    <div class="header">
      <h1>'.e($title).'</h1>
      <p class="sub">Generado el '.now()->format('Y-m-d H:i').'</p>
      <div class="chips">'.$chips.'</div>
    </div>

    '.$chartHtml.'

    <table>
      <thead><tr>'.$head.'</tr></thead>
      <tbody>'.$body.'</tbody>
    </table>
    </body></html>';
    }

    /** Export genérico para reutilizar en los hijos */
    protected function exportExcel(string $title, array $rows)
    {
        if (class_exists(\Rap2hpoutre\FastExcel\FastExcel::class)) {
            $rows = empty($rows) ? [['Mensaje' => 'Sin datos']] : $rows;
            $file = storage_path('app/tmp/'.Str::slug($title).'-'.time().'.xlsx');
            @mkdir(dirname($file), 0775, true);
            (new \Rap2hpoutre\FastExcel\FastExcel(collect($rows)))->export($file);
            return response()->download($file)->deleteFileAfterSend(true);
        }

        $csv  = $this->arrayToCsv($rows);
        $name = Str::slug($title).'-'.time().'.csv';
        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$name\"",
        ]);
    }

    protected function exportPdf(string $title, array $rows, array $meta = [])
    {
        $html = $this->simpleTableHtml($title, $rows, $meta);

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            return $pdf->download(\Illuminate\Support\Str::slug($title).'-'.time().'.pdf');
        }
        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return \Illuminate\Support\Facades\Response::make($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.\Illuminate\Support\Str::slug($title).'-'.time().'.pdf"',
            ]);
        }
        return \Illuminate\Support\Facades\Response::make($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.\Illuminate\Support\Str::slug($title).'-'.time().'.html"',
        ]);
    }
}
