<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use MongoDB\BSON\ObjectId;

// MODELOS (MongoDB\Laravel\Eloquent\Model)
use App\Models\Tecnica;
use App\Models\Actividad;
use App\Models\Cita;
use App\Models\Bitacora;
use App\Models\Encuesta;
use App\Models\Recompensa;
use App\Models\Persona;
use App\Models\User;

class ReporteController extends Controller
{
    /* ==================== Helpers ==================== */

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

    /* ============ Mapeos Users <-> Personas ============ */

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

    protected function userIdsByCohorte(?string $grupo): array
    {
        $grupo = trim((string)$grupo);
        if ($grupo === '') return [];

        $personaIds = Persona::query()
            ->where(function($q) use ($grupo) {
                $q->where('cohorte', $grupo)
                  ->orWhere('cohorte', 'like', "%{$grupo}%");
            })
            ->limit(2000)
            ->pluck('_id')
            ->map(fn($id)=>(string)$id)
            ->values()
            ->all();

        if (empty($personaIds)) return [];
        return User::whereIn('persona_id', $this->mixedIn($personaIds))
            ->pluck('_id')->map(fn($id)=>(string)$id)->values()->all();
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

    /* ==================== 1) Top técnicas ==================== */
    public function topTecnicas(Request $r)
    {
        $q = Actividad::query();
        $campoFecha = 'fechaMaxima';
        $q = $this->rangoFechas($r, $campoFecha, $q);

        if (($grupoParam = $this->d($r, 'grupo')) !== null) {
            $userIds = $this->userIdsByCohorte($grupoParam);
            if (empty($userIds)) {
                return response()->json(['labels'=>[], 'data'=>[]]); // filtro solicitado pero sin matches
            }
            $q->where('participantes', 'elemMatch', [
                'user_id' => ['$in' => $this->mixedIn($userIds)]
            ]);
        }

        $rows = $q->get(['tecnica_id']);
        $conteo = [];
        foreach ($rows as $row) {
            $tid = (string)($row->tecnica_id ?? '');
            if ($tid === '') continue;
            $conteo[$tid] = ($conteo[$tid] ?? 0) + 1;
        }

        $labels = []; $data = [];
        if (!empty($conteo)) {
            arsort($conteo);
            $top = array_slice($conteo, 0, 4, true);
            $tecs = Tecnica::whereIn('_id', array_keys($top))
                ->get(['_id','nombre'])->keyBy('_id');
            foreach ($top as $tid => $cnt) {
                $labels[] = $this->s(optional($tecs->get($tid))->nombre) ?: 'Técnica';
                $data[]   = (int)$cnt;
            }
        }

        return response()->json(['labels'=>$labels,'data'=>$data]);
    }

    /* ============== 2) Actividades por alumno =============== */
    public function actividadesPorAlumno(Request $r)
    {
        $q = Actividad::query();
        $campoFecha = 'fechaMaxima';
        $q = $this->rangoFechas($r, $campoFecha, $q);

        $alumnoParam = $this->d($r, 'alumno');
        $idsAlumno = $this->userIdsByAlumnoSearch($alumnoParam);

        if ($alumnoParam !== null && empty($idsAlumno)) {
            return response()->json(['rows'=>[]]); // filtro solicitado y sin matches
        }
        if (!empty($idsAlumno)) {
            $q->where('participantes', 'elemMatch', [
                'user_id' => ['$in' => $this->mixedIn($idsAlumno)]
            ]);
        }

        $acts = $q->latest($campoFecha)->limit(500)
            ->get(['nombre','descripcion','tecnica_id',$campoFecha,'participantes']);

        $tecMap = Tecnica::whereIn('_id', $acts->pluck('tecnica_id')->filter()->unique()->values()->all())
            ->get(['_id','nombre'])->keyBy('_id');

        $userIds = [];
        foreach ($acts as $a) foreach ((array)$a->participantes as $p) $userIds[] = (string)($p['user_id'] ?? '');
        $personaByUser = $this->personaMapByUserIds(array_values(array_unique($userIds)));

        $rows = [];
        foreach ($acts as $a) {
            $tec   = optional($tecMap->get((string)$a->tecnica_id))->nombre ?: '—';
            $fecha = $a->{$campoFecha} ?? $a->created_at;

            foreach ((array)$a->participantes as $p) {
                $uid = (string)($p['user_id'] ?? '');
                if ($uid === '') continue;
                if (!empty($idsAlumno) && !in_array($uid, $idsAlumno, true)) continue;

                $per = $personaByUser->get($uid);
                $alumnoNom = $per ? trim("{$per->nombre} {$per->apellidoPaterno} {$per->apellidoMaterno}") : 'Alumno';
                $mat = $per->matricula ?? '—';

                $rows[] = [
                    'alumno'      => $alumnoNom,
                    'matricula'   => $mat,
                    'tecnica'     => $tec,
                    'fecha'       => $fecha,
                    'titulo'      => (string)($a->nombre ?? ''),
                    'descripcion' => (string)($a->descripcion ?? ''),
                ];
            }
        }
        return response()->json(['rows'=>$rows]);
    }

    /* ==================== 3) Citas por alumno ==================== */
    public function citasPorAlumno(Request $r)
    {
        $q = Cita::query();
        $q = $this->rangoFechas($r, 'fecha_cita', $q);

        $alumnoParam = $this->d($r, 'alumno');
        $idsUser = $this->userIdsByAlumnoSearch($alumnoParam);

        if ($alumnoParam !== null && empty($idsUser)) {
            return response()->json(['rows'=>[]]);
        }
        if (!empty($idsUser)) {
            $q->whereIn('alumno_id', $this->mixedIn($idsUser));
        }

        $citas = $q->orderBy('fecha_cita', 'desc')->limit(600)
            ->get(['alumno_id','fecha_cita','estado','motivo']);

        $personaByUser = $this->personaMapByUserIds(
            $citas->pluck('alumno_id')->map(fn($v)=>(string)$v)->unique()->values()->all()
        );

        $rows = [];
        foreach ($citas as $c) {
            $per = $personaByUser->get((string)$c->alumno_id);
            $rows[] = [
                'alumno'    => $per ? trim("{$per->nombre} {$per->apellidoPaterno} {$per->apellidoMaterno}") : 'Alumno',
                'matricula' => $per->matricula ?? '—',
                'fecha'     => $c->fecha_cita ?? $c->created_at,
                'estado'    => (string)($c->estado ?? 'Pendiente'),
                'motivo'    => (string)($c->motivo ?? ''),
            ];
        }
        return response()->json(['rows'=>$rows]);
    }

    /* ==================== 4) Bitácoras por alumno ==================== */
    public function bitacorasPorAlumno(Request $r)
    {
        $q = Bitacora::query();
        $q = $this->rangoFechas($r, 'fecha', $q);

        $alumnoParam = $this->d($r, 'alumno');
        $idsUser = $this->userIdsByAlumnoSearch($alumnoParam);

        if ($alumnoParam !== null && empty($idsUser)) {
            return response()->json(['rows'=>[]]);
        }
        if (!empty($idsUser)) {
            $q->whereIn('alumno_id', $this->mixedIn($idsUser));
        }

        $bits = $q->get(['alumno_id']);
        $conteo = [];
        foreach ($bits as $b) {
            $uid = (string)($b->alumno_id ?? '');
            if ($uid==='') continue;
            $conteo[$uid] = ($conteo[$uid] ?? 0) + 1;
        }

        $personaByUser = $this->personaMapByUserIds(array_keys($conteo));

        $rows = [];
        foreach ($conteo as $uid => $total) {
            $per = $personaByUser->get($uid);
            $rows[] = [
                'alumno'    => $per ? trim("{$per->nombre} {$per->apellidoPaterno} {$per->apellidoMaterno}") : 'Alumno',
                'matricula' => $per->matricula ?? '—',
                'total'     => (int)$total,
            ];
        }
        return response()->json(['rows'=>array_values($rows)]);
    }

    /* ==================== 5) Resultados de encuestas ==================== */
    public function encuestasResultados(Request $r)
    {
        $q = Encuesta::query();
        $q = $this->rangoFechas($r, 'fechaFinalizacion', $q);

        if ($enc = $this->d($r, 'encuesta')) {
            $q->where(function($w) use ($enc){
                $w->where('_id', $enc)
                  ->orWhere('titulo', 'like', "%{$enc}%");
            });
        }

        $encs = $q->limit(30)->get(['_id','titulo','cuestionario']);
        $labels = []; $data = [];
        foreach ($encs as $e) {
            $total = 0;
            foreach ((array)$e->cuestionario as $preg) {
                $resps = Arr::get($preg, 'respuestas_por_usuario', []);
                $total += is_array($resps) ? count($resps) : 0;
            }
            $labels[] = $this->s($e->titulo) ?: 'Encuesta';
            $data[]   = $total;
        }
        return response()->json(['labels'=>$labels,'data'=>$data]);
    }

    /* ==================== 6) Recompensas canjeadas ==================== */
    public function recompensasCanjeadas(Request $r)
    {
        $q = Recompensa::query();
        if ($tipo = $this->d($r, 'tipo')) $q->where('nombre', 'like', "%{$tipo}%");

        $recs = $q->get(['_id','nombre','canjeo']);

        $uids = [];
        foreach ($recs as $rec) foreach ((array)$rec->canjeo as $c) $uids[] = (string)($c['usuario_id'] ?? '');
        $personaByUser = $this->personaMapByUserIds(array_values(array_unique($uids)));

        $d = $this->d($r, 'desde'); $h = $this->d($r, 'hasta');
        $D = $d ? Carbon::parse($d)->startOfDay() : null;
        $H = $h ? Carbon::parse($h)->endOfDay()   : null;

        $rows = [];
        foreach ($recs as $rec) {
            $tipoNombre = $this->s($rec->nombre);
            foreach ((array)$rec->canjeo as $c) {
                $uid = (string)($c['usuario_id'] ?? '');
                $fecha = isset($c['fechaCanjeo']) ? Carbon::parse($c['fechaCanjeo']) : null;

                if ($D && $fecha && $fecha->lt($D)) continue;
                if ($H && $fecha && $fecha->gt($H)) continue;

                $per = $personaByUser->get($uid);
                $rows[] = [
                    'alumno'     => $per ? trim("{$per->nombre} {$per->apellidoPaterno} {$per->apellidoMaterno}") : 'Alumno',
                    'matricula'  => $per->matricula ?? '—',
                    'tipo'       => $tipoNombre,
                    'recompensa' => $tipoNombre,
                    'puntos'     => (int)($c['puntos'] ?? 0),
                    'fecha'      => $fecha ? $fecha->toDateTimeString() : null,
                ];
            }
        }
        return response()->json(['rows'=>$rows]);
    }

    /* ==================== Exportador ==================== */
    public function export(Request $r)
    {
        $tipo    = strtolower($this->d($r, 'tipo', 'pdf'));
        $reporte = $this->d($r, 'reporte', '');

        switch ($reporte) {
            case 'top-tecnicas':
                $payload = $this->topTecnicas($r)->getData(true);
                $title   = 'Top cuatro técnicas más utilizadas';
                $rows    = $this->rowsFromChart($payload, 'Técnica', 'Total');
                break;
            case 'actividades-por-alumno':
                $payload = $this->actividadesPorAlumno($r)->getData(true);
                $title   = 'Actividades por alumno';
                $rows    = $payload['rows'] ?? [];
                break;
            case 'citas-por-alumno':
                $payload = $this->citasPorAlumno($r)->getData(true);
                $title   = 'Citas por alumno';
                $rows    = $payload['rows'] ?? [];
                break;
            case 'bitacoras-por-alumno':
                $payload = $this->bitacorasPorAlumno($r)->getData(true);
                $title   = 'Bitácoras por alumno';
                $rows    = $payload['rows'] ?? [];
                break;
            case 'encuestas-resultados':
                $payload = $this->encuestasResultados($r)->getData(true);
                $title   = 'Resultados de encuestas';
                $rows    = $this->rowsFromChart($payload, 'Encuesta', 'Respuestas');
                break;
            case 'recompensas-canjeadas':
                $payload = $this->recompensasCanjeadas($r)->getData(true);
                $title   = 'Recompensas canjeadas';
                $rows    = $payload['rows'] ?? [];
                break;
            default:
                return response()->json(['error'=>'Reporte no reconocido'], 400);
        }

        $meta = [];
        $desde = $this->d($r,'desde'); $hasta = $this->d($r,'hasta');
        $meta['rango'] = ($desde && $hasta) ? "$desde a $hasta" : 'Todas las fechas';

        if ($g = $this->d($r,'grupo'))    $meta['Cohorte']   = $g;
        if ($a = $this->d($r,'alumno'))   $meta['Alumno']    = $a;
        if ($e = $this->d($r,'encuesta')) $meta['Encuesta']  = $e;
        if ($t = $this->d($r,'tipo'))     $meta['Tipo']      = $t;

        if ($tipo === 'excel')  return $this->exportExcel($title, $rows);
        if ($tipo === 'pdf')    return $this->exportPdf($title, $rows, $meta);

        return response()->json(['error'=>'Tipo de exportación inválido'], 400);
    }

    protected function rowsFromChart(array $payload, string $k1, string $k2): array
    {
        $labels = $payload['labels'] ?? [];
        $data   = $payload['data'] ?? [];
        $rows = [];
        foreach ($labels as $i => $label) {
            $rows[] = [ $k1 => $this->s($label), $k2 => (int)($data[$i] ?? 0) ];
        }
        return $rows;
    }

    /* ---- Excel ---- */
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

    /* ---- PDF ---- */
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

    /* ---- Autocomplete alumnos ---- */
    public function suggestAlumnos(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        $users = \App\Models\User::query()
            ->where('rol', 'estudiante')
            ->when($q !== '', function ($w) use ($q) {
                $w->whereHas('persona', function ($p) use ($q) {
                    $p->where('nombre', 'like', "%{$q}%")
                      ->orWhere('apellidoPaterno', 'like', "%{$q}%")
                      ->orWhere('apellidoMaterno', 'like', "%{$q}%")
                      ->orWhere('matricula', 'like', "%{$q}%");
                });
            })
            ->with(['persona:_id,nombre,apellidoPaterno,apellidoMaterno,matricula'])
            ->limit(80)
            ->get(['_id','persona_id']);

        return response()->json(
            $users->map(function($u){
                $p = $u->persona;
                return [
                    'id'        => (string)$u->_id,
                    'nombre'    => (string)($p->nombre ?? ''),
                    'apellidoPaterno' => (string)($p->apellidoPaterno ?? ''),
                    'apellidoMaterno' => (string)($p->apellidoMaterno ?? ''),
                    'matricula' => (string)($p->matricula ?? ''),
                ];
            })->values()
        );
    }

    protected function simpleTableHtml(string $title, array $rows, array $meta = []): string
    {
        $cols = !empty($rows) ? array_keys((array)$rows[0]) : ['Mensaje'];
        if (empty($rows)) $rows = [['Mensaje' => 'Sin datos']];

        $chips = '';
        $rango = $meta['rango'] ?? 'Todas las fechas';
        $chips .= '<span class="chip"><b>Rango:</b> '.e($rango).'</span>';
        foreach ($meta as $k => $v) {
            if ($k === 'rango') continue;
            $chips .= '<span class="chip"><b>'.e($k).':</b> '.e($v).'</span>';
        }

        $head = '';
        foreach ($cols as $c) $head .= '<th>'.e($c).'</th>';

        $body = '';
        $i = 0;
        foreach ($rows as $r) {
            $i++;
            $cells = '';
            foreach ($cols as $c) $cells .= '<td>'.e($r[$c] ?? '').'</td>';
            $body .= '<tr class="'.($i%2? 'odd':'even').'">'.$cells.'</tr>';
        }

        return '<!DOCTYPE html><html lang="es"><meta charset="utf-8">
    <title>'.e($title).'</title>
    <style>
    *{ box-sizing:border-box; }
    body{ font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu; color:#0f172a; margin:24px; }
    .header{ padding:16px 18px; border:1px solid #e5e7eb; border-radius:14px;
            background: linear-gradient(180deg,#fafbff,#ffffff); }
    h1{ margin:0 0 2px 0; font-size:20px; }
    .sub{ color:#64748b; margin:0 0 10px 0; font-size:12px; }
    .chips{ display:flex; gap:8px; flex-wrap:wrap; }
    .chip{ display:inline-flex; gap:6px; align-items:center; padding:6px 10px;
            border:1px solid #e5e7eb; border-radius:999px; background:#fff; font-size:12px; }
    table{ width:100%; border-collapse:collapse; margin-top:14px; border:1px solid #e5e7eb; }
    thead th{ text-align:left; padding:8px; font-size:12px; background:#f7f8fc; border-bottom:1px solid #e5e7eb; }
    tbody td{ padding:8px; font-size:12px; border-bottom:1px solid #f1f5f9; }
    tbody tr.even{ background:#fbfbff; }
    footer{ margin-top:8px; color:#94a3b8; font-size:11px; }
    </style>
    <body>
    <div class="header">
        <h1>'.e($title).'</h1>
        <p class="sub">Generado el '.now()->format('Y-m-d H:i').'</p>
        <div class="chips">'.$chips.'</div>
    </div>
    <table>
        <thead><tr>'.$head.'</tr></thead>
        <tbody>'.$body.'</tbody>
    </table>
    <footer>Reporte generado por el sistema.</footer>
    </body></html>';
    }
}
