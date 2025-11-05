<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId as MongoObjectId;
use App\Models\Test;
use App\Models\User;

class TestRespuestaController extends Controller
{
    public function index(string $id, Request $request)
    {
        // 1) Validar ObjectId y cargar Test
        try { $oid = new MongoObjectId($id); }
        catch (\Throwable $e) { return response()->json(['message' => 'ID de test no válido'], 422); }

        $test = Test::find($oid);
        if (!$test) return response()->json(['message' => 'Test no encontrado'], 404);

        $cuestionario = is_array($test->cuestionario ?? null) ? $test->cuestionario : [];

        // 2) Aplanar por usuario
        $byUser = []; // uid => ['usuario_id','respuestas'=>[], 'ultima_fecha', 'total_respuestas', 'nombre','email']
        foreach ($cuestionario as $idx => $preg) {
            $p = is_array($preg) ? $preg : (array)$preg;

            $numero   = $idx + 1;
            $texto    = (string)($p['pregunta'] ?? '');
            $tipo     = (string)($p['tipo'] ?? '');
            $resps    = $p['respuestas_por_usuario'] ?? [];
            if (!is_array($resps)) $resps = (array)$resps;

            foreach ($resps as $r0) {
                $r = is_array($r0) ? $r0 : (array)$r0;
                $uid   = (string)($r['usuario_id'] ?? '');
                if ($uid === '') continue;

                // Normaliza respuesta a array<string>
                $val   = $r['respuesta'] ?? null;
                $vals  = [];
                if (is_array($val)) {
                    $vals = array_values(array_filter(array_map(fn($x)=>trim((string)$x), $val), fn($x)=>$x!==''));
                } elseif (is_string($val)) {
                    $parts = array_values(array_filter(array_map('trim', explode(',', $val)), fn($x)=>$x!==''));
                    $vals = $parts ?: [trim($val)];
                } elseif (!is_null($val)) {
                    $vals = [ (string)$val ];
                }

                if (!isset($byUser[$uid])) {
                    $byUser[$uid] = [
                        'usuario_id'       => $uid,
                        'respuestas'       => [],
                        'ultima_fecha'     => null,
                        'total_respuestas' => 0,
                        'nombre'           => null,
                        'email'            => null,
                    ];
                }

                $byUser[$uid]['respuestas'][] = [
                    'numero'   => $numero,
                    'pregunta' => $texto,
                    'tipo'     => $tipo,
                    'fecha'    => $r['fecha'] ?? null,
                    'valores'  => $vals,
                ];

                $byUser[$uid]['total_respuestas']++;

                $f = $r['fecha'] ?? null;
                if ($f) {
                    $cur = $byUser[$uid]['ultima_fecha'];
                    $byUser[$uid]['ultima_fecha'] = $cur ? (strcmp((string)$f, (string)$cur) >= 0 ? $f : $cur) : $f;
                }
            }
        }

        // Si no hay nadie, responde vacío pero en el formato correcto
        if (empty($byUser)) {
            return response()->json([
                'test' => ['id' => (string)$test->_id, 'nombre' => (string)$test->nombre],
                'respondents' => [],
            ], 200);
        }

        // 3) Enriquecer con User + Persona (via relación)
        $uids = array_keys($byUser);
        $oidList = [];
        foreach ($uids as $u) if (preg_match('/^[a-f0-9]{24}$/i', $u)) $oidList[] = new MongoObjectId($u);

        $users = User::with('persona')
            ->when(!empty($oidList), fn($q)=>$q->whereIn('_id', $oidList))
            ->get();

        foreach ($users as $u) {
            $key = (string)$u->_id;
            if (!isset($byUser[$key])) continue;

            $nombre = $u->name ?? null;
            if (!$nombre && $u->relationLoaded('persona') && $u->persona) {
                $p = $u->persona;
                $nombre = trim(implode(' ', array_filter([
                    $p->nombre ?? null,
                    $p->apellidoPaterno ?? null,
                    $p->apellidoMaterno ?? null,
                ])));
            }
            $byUser[$key]['nombre'] = $nombre ?: '—';
            $byUser[$key]['email']  = $u->email ?? null;
        }

        // 4) Orden por última fecha desc (sin filtrar aquí; el front filtra)
        $respondents = array_values($byUser);
        usort($respondents, fn($a,$b)=>strcmp((string)($b['ultima_fecha']??''), (string)($a['ultima_fecha']??'')));

        // 5) Payload final
        return response()->json([
            'test' => [
                'id'     => (string)$test->_id,
                'nombre' => (string)$test->nombre,
            ],
            'respondents' => $respondents,
        ], 200);
    }
}
