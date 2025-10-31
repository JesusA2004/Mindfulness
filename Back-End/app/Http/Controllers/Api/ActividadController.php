<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActividadRequest;
use App\Http\Resources\ActividadResource;
use App\Models\Actividad;
use App\Models\Persona;
use App\Models\Tecnica;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;

class ActividadController extends Controller
{
    /**
     * GET /api/actividades
     */
    public function index(Request $request): JsonResponse
    {
        $q = Actividad::query();

        // Filtros
        if ($docenteId = $request->string('docente_id')->toString()) {
            $q->where('docente_id', (string) $docenteId);
        }
        if ($desde = $request->string('desde')->toString()) {
            $q->where('fechaAsignacion', '>=', $desde);
        }
        if ($hasta = $request->string('hasta')->toString()) {
            $q->where('fechaAsignacion', '<=', $hasta);
        }

        // Filtrado por cohorte (string o array en persona.cohorte)
        if ($cohorte = $request->string('cohorte')->toString()) {
            $cohortUsers = User::query()
                ->whereIn('rol', ['estudiante', 'alumno'])
                ->where(function ($w) use ($cohorte) {
                    $w->whereIn('persona.cohorte', [$cohorte]);
                })
                ->pluck('_id')
                ->map(fn ($v) => (string) $v)
                ->all();

            if (!empty($cohortUsers)) {
                $q->where(function ($w) use ($cohortUsers) {
                    foreach ($cohortUsers as $uid) {
                        $w->orWhere('participantes', 'elemMatch', ['user_id' => (string) $uid]);
                    }
                });
            } else {
                return response()->json([
                    'registros' => [],
                    'enlaces'   => ['primero' => null, 'ultimo' => null, 'anterior' => null, 'siguiente' => null],
                ], 200);
            }
        }

        $q->orderBy('fechaAsignacion', 'desc')->orderBy('_id', 'desc');

        $perPage   = (int) ($request->integer('perPage') ?: 6);
        $paginator = $q->paginate($perPage)->appends($request->query());

        // Base con Resource
        $registros = collect(ActividadResource::collection($paginator)->resolve());

        // ===== Enriquecer con TÉCNICAS (nombre/categoría) en un solo query =====
        $tecnicaIds = $registros->pluck('tecnica_id')
            ->filter(fn ($v) => !empty($v))
            ->map(function ($v) {
                try { return $v instanceof ObjectId ? $v : new ObjectId((string) $v); }
                catch (\Throwable $e) { return null; }
            })
            ->filter()
            ->unique()
            ->values();

        $tecnicas = $tecnicaIds->isEmpty()
            ? collect()
            : Tecnica::whereIn('_id', $tecnicaIds)->get(['_id','nombre','categoria'])
                ->keyBy(fn ($t) => (string) $t->_id);

        $registros = $registros->map(function (array $a) use ($tecnicas) {
            $key = (string) ($a['tecnica_id'] ?? '');
            $a['tecnica'] = ($key && isset($tecnicas[$key])) ? $tecnicas[$key] : null;
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

    // GET /api/actividades/asignadas  (solo las del usuario autenticado)
    public function asignadas(Request $request): JsonResponse
    {
        $u = auth('api')->user();
        if (!$u) return response()->json(['message' => 'No autenticado'], 401);

        $uid = (string)($u->_id ?? $u->id ?? '');

        $q = Actividad::query();

        // participantes puede estar como ARRAY o quedó STRING en algunos docs → cubrir ambos
        $q->where(function ($w) use ($uid) {
            $w->where('participantes', 'elemMatch', ['user_id' => $uid])
            ->orWhere('participantes', 'regex', new Regex('"user_id":"'.preg_quote($uid, '/').'"', 'i'));
        });

        // filtro opcional ?estado=Pendiente|Completado|Omitido
        if ($estado = $request->query('estado')) {
            $estado = ucfirst(strtolower($estado));
            if (in_array($estado, ['Pendiente','Completado','Omitido'], true)) {
                $q->where(function ($w) use ($uid, $estado) {
                    $w->where('participantes', 'elemMatch', ['user_id' => $uid, 'estado' => $estado])
                    ->orWhere('participantes', 'regex', new Regex('"user_id":"'.preg_quote($uid, '/').'"[^}]*"estado":"'.$estado.'"', 'i'));
                });
            }
        }

        $q->orderBy('fechaAsignacion', 'desc')->orderBy('_id', 'desc');

        $perPage   = (int)($request->integer('perPage') ?: 12);
        $paginator = $q->paginate($perPage)->appends($request->query());

        // enriquecer con técnica (coincide con tu modelo Tecnica)
        $registros = collect(ActividadResource::collection($paginator)->resolve());
        $ids = $registros->pluck('tecnica_id')->filter()->unique()->values();

        $tecnicas = $ids->isEmpty()
            ? collect()
            : Tecnica::whereIn('_id', $ids)
                ->get(['_id','nombre','categoria','dificultad','duracion','recursos'])
                ->keyBy(fn($t) => (string)$t->_id);

        $registros = $registros->map(function(array $a) use ($tecnicas){
            // técnica
            $k = (string)($a['tecnica_id'] ?? '');
            $a['tecnica'] = $k && isset($tecnicas[$k]) ? $tecnicas[$k] : null;
            // participantes: string -> array
            $p = $a['participantes'] ?? [];
            if (is_string($p)) {
                $dec = json_decode($p, true);
                $p = is_array($dec) ? $dec : [];
            }
            $a['participantes'] = $p;
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

    /**
     * POST /api/actividades
     */
    public function store(ActividadRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Fecha de asignación hoy (MX)
        $data['fechaAsignacion'] = Carbon::now('America/Mexico_City')->toDateString();

        // Normalizaciones a string
        $data['docente_id'] = (string) $data['docente_id'];
        $data['tecnica_id'] = (string) $data['tecnica_id'];

        // Normalizar participantes
        if (isset($data['participantes']) && is_array($data['participantes'])) {
            $data['participantes'] = array_values(array_map(function ($p) {
                return [
                    'user_id' => (string) ($p['user_id'] ?? ''),
                    'estado'  => in_array(($p['estado'] ?? 'Pendiente'), ['Pendiente','Completado','Omitido'], true)
                        ? $p['estado'] : 'Pendiente',
                ];
            }, $data['participantes']));
        }

        $actividad = Actividad::create($data);

        // Respuesta con técnica adjunta
        $payload = (new ActividadResource($actividad))->resolve();
        $payload['tecnica'] = $this->findTecnicaShallow($payload['tecnica_id'] ?? null);

        return response()->json([
            'mensaje'   => 'Actividad creada correctamente.',
            'actividad' => $payload,
        ], 201);
    }

    /**
     * GET /api/actividades/{actividad}
     */
    public function show(Actividad $actividad): JsonResponse
    {
        $payload = (new ActividadResource($actividad))->resolve();
        $payload['tecnica'] = $this->findTecnicaShallow($payload['tecnica_id'] ?? null);

        return response()->json([
            'actividad' => $payload,
        ], 200);
    }

    /**
     * PUT /api/actividades/{actividad}
     */
    public function update(ActividadRequest $request, Actividad $actividad): JsonResponse
    {
        $data = $request->validated();

        // Mantener fechaAsignacion si no llega
        $data['fechaAsignacion'] = $data['fechaAsignacion'] ?? $actividad->fechaAsignacion;
        $data['docente_id']      = (string) $data['docente_id'];
        $data['tecnica_id']      = (string) $data['tecnica_id'];

        if (isset($data['participantes']) && is_array($data['participantes'])) {
            $data['participantes'] = array_values(array_map(function ($p) {
                return [
                    'user_id' => (string) ($p['user_id'] ?? ''),
                    'estado'  => in_array(($p['estado'] ?? 'Pendiente'), ['Pendiente','Completado','Omitido'], true)
                        ? $p['estado'] : 'Pendiente',
                ];
            }, $data['participantes']));
        }

        $actividad->update($data);

        $payload = (new ActividadResource($actividad))->resolve();
        $payload['tecnica'] = $this->findTecnicaShallow($payload['tecnica_id'] ?? null);

        return response()->json([
            'mensaje'   => 'Actividad actualizada correctamente.',
            'actividad' => $payload,
        ], 200);
    }

    /**
     * DELETE /api/actividades/{actividad}
     */
    public function destroy(Actividad $actividad): JsonResponse
    {
        $actividad->delete();

        return response()->json([
            'mensaje' => 'Actividad eliminada correctamente.',
        ], 200);
    }

    /* ======================= PATCH SOLO-ESTADO (alumno en sesión) ======================= */
    /**
     * PATCH /api/actividades/{id}/estado
     * Body: { "estado": "Pendiente|Completado|Omitido" }
     * Afecta únicamente al participante = usuario autenticado (por _id/id).
     */
    public function patchEstado(Request $request, string $id): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $validated = $request->validate([
            'estado' => ['required','string', Rule::in(['Pendiente','Completado','Omitido'])],
        ]);

        Log::info('[PATCH estado] intento', [
            'actividad_id' => $id,
            'user' => (string)($user->_id ?? $user->id ?? '')
        ]);

        $actividad = $this->findActividadById($id);
        if (!$actividad) {
            Log::warning('[PATCH estado] Actividad no encontrada', ['actividad_id' => $id]);
            return response()->json(['message' => 'Actividad no encontrada'], 404);
        }

        $uid = (string)($user->_id ?? $user->id ?? '');

        $participantes = $this->normalizeParticipantes($actividad->participantes);
        // elimina duplicados del mismo alumno
        $participantes = array_values(array_filter($participantes, fn($p) => (string)($p['user_id'] ?? '') !== $uid));
        // inserta el estado nuevo
        $participantes[] = ['user_id' => $uid, 'estado' => $validated['estado']];

        $actividad->participantes = $participantes;

        if ($validated['estado'] === 'Completado') {
            $actividad->fechaFinalizacion = now('America/Mexico_City')->toDateString();
        }

        $actividad->save();

        Log::info('[PATCH estado] OK', [
            'actividad_id' => (string)($actividad->_id ?? $actividad->id ?? ''),
            'estado'       => $validated['estado']
        ]);

        return response()->json([
            'mensaje'      => 'Estado actualizado.',
            'actividad_id' => (string)($actividad->_id ?? $actividad->id ?? ''),
            'user_id'      => $uid,
            'estado'       => $validated['estado'],
        ], 200);
    }

    /* -------------------- helpers privados -------------------- */

    private function cohortesDe(User $u): array
    {
        $out = [];
        $raw = $u->persona->cohorte ?? $u->persona['cohorte'] ?? null;
        if ($raw) {
            if (is_array($raw)) { foreach ($raw as $c) { $s = trim((string) $c); if ($s !== '') $out[] = $s; } }
            elseif (is_string($raw)) { $s = trim($raw); if ($s !== '') $out[] = $s; }
        }
        if (empty($out) && !empty($u->persona_id)) {
            $p = Persona::find($u->persona_id);
            if ($p) {
                $raw = $p->cohorte ?? null;
                if (is_array($raw)) { foreach ($raw as $c) { $s = trim((string) $c); if ($s !== '') $out[] = $s; } }
                elseif (is_string($raw)) { $s = trim($raw); if ($s !== '') $out[] = $s; }
            }
        }
        return array_values(array_unique($out));
    }

    private function findTecnicaShallow($id)
    {
        if (!$id) return null;
        try {
            $oid = $id instanceof ObjectId ? $id : new ObjectId((string) $id);
        } catch (\Throwable $e) {
            return null;
        }
        return Tecnica::where('_id', $oid)->first(['_id','nombre','categoria']);
    }

    private function findActividadById(string $id): ?Actividad
    {
        try {
            if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
                $oid = new ObjectId($id);
                if ($found = Actividad::where('_id', $oid)->first()) return $found;
            }
        } catch (\Throwable $e) {}

        if ($found = Actividad::where('_id', $id)->orWhere('id', $id)->first()) return $found;
        return Actividad::find($id) ?: null;
    }

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
