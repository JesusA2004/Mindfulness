<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActividadResource;
use App\Models\Actividad;
use App\Models\Tecnica;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use MongoDB\BSON\Regex;
use MongoDB\BSON\ObjectId;

class ActividadesAsignadasController extends Controller
{
    /**
     * GET /api/alumno/actividades
     * Opcional: ?estado=Pendiente|Completado|Omitido  ?perPage=12
     */
    public function index(Request $request): JsonResponse
    {
        $u = auth('api')->user();
        if (!$u) {
            return response()->json(['message' => 'No autenticado'], 401);
        }
        $uid = (string)($u->_id ?? $u->id ?? '');

        $q = Actividad::query();

        // participantes puede estar como ARRAY o como STRING en algunos docs
        $q->where(function ($w) use ($uid) {
            $w->where('participantes', 'elemMatch', ['user_id' => $uid])
              ->orWhere('participantes', 'regex', new Regex('"user_id":"'.preg_quote($uid, '/').'"', 'i'));
        });

        // filtro opcional de estado
        if ($estado = $request->query('estado')) {
            $estado = ucfirst(strtolower($estado));
            if (in_array($estado, ['Pendiente','Completado','Omitido'], true)) {
                $q->where(function ($w) use ($uid, $estado) {
                    $w->where('participantes', 'elemMatch', ['user_id' => $uid, 'estado' => $estado])
                      ->orWhere('participantes', 'regex',
                          new Regex('"user_id":"'.preg_quote($uid, '/').'"[^}]*"estado":"'.$estado.'"', 'i')
                      );
                });
            }
        }

        $q->orderBy('fechaAsignacion', 'desc')->orderBy('_id', 'desc');

        $perPage   = (int)($request->integer('perPage') ?: 12);
        $paginator = $q->paginate($perPage)->appends($request->query());

        // payload base
        $registros = collect(ActividadResource::collection($paginator)->resolve());

        // enriquecer con técnica (nombre, categoría, dificultad, duración, recursos)
        $ids = $registros->pluck('tecnica_id')
            ->filter(fn($v) => !empty($v))
            ->map(function ($v) { try { return $v instanceof ObjectId ? $v : new ObjectId((string)$v); } catch (\Throwable $e) { return null; } })
            ->filter()
            ->unique()
            ->values();

        $tecnicas = $ids->isEmpty()
            ? collect()
            : Tecnica::whereIn('_id', $ids)
                ->get(['_id','nombre','categoria','dificultad','duracion','recursos'])
                ->keyBy(fn($t) => (string)$t->_id);

        $registros = $registros->map(function(array $a) use ($tecnicas){
            $k = (string)($a['tecnica_id'] ?? '');
            $a['tecnica'] = ($k && isset($tecnicas[$k])) ? $tecnicas[$k] : null;
            return $a;
        })->values();

        return response()->json([
            'registros' => $registros,
            'enlaces'   => [
                'primero'   => $paginator->url(1),
                'ultimo'    => $paginator->url($paginator->lastPage()),
                'anterior'  => $paginator->previousPageUrl(),
                'siguiente' => $paginator->nextPageUrl(),
            ],
        ], 200);
    }
}
