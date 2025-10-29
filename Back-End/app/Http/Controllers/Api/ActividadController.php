<?php

namespace App\Http\Controllers\Api;

use App\Models\Actividad;
use App\Models\User;
use App\Models\Persona;
use Illuminate\Http\Request;
use App\Http\Requests\ActividadRequest;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActividadResource;
use Illuminate\Support\Carbon;

class ActividadController extends Controller
{
    /**
     * GET /api/actividades
     */
    public function index(Request $request): JsonResponse
    {
        $q = Actividad::query();

        if ($docenteId = $request->string('docente_id')->toString()) {
            $q->where('docente_id', (string) $docenteId);
        }
        if ($desde = $request->string('desde')->toString()) {
            $q->where('fechaAsignacion', '>=', $desde);
        }
        if ($hasta = $request->string('hasta')->toString()) {
            $q->where('fechaAsignacion', '<=', $hasta);
        }

        // 🔧 Filtrado por cohorte (opcional)
        if ($cohorte = $request->string('cohorte')->toString()) {
            // Obtén IDs de alumnos de ese cohorte (campo puede ser string o array)
            $cohortUsers = User::query()
                ->whereIn('rol', ['estudiante','alumno'])
                ->where(function ($w) use ($cohorte) {
                    // $in sobre persona.cohorte -> funciona para string o array con Jenssegers
                    $w->whereIn('persona.cohorte', [$cohorte]);
                })
                ->pluck('_id')
                ->map(fn($v) => (string) $v)
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
                    'enlaces'   => ['primero'=>null,'ultimo'=>null,'anterior'=>null,'siguiente'=>null],
                ]);
            }
        }

        $q->orderBy('fechaAsignacion', 'desc')->orderBy('_id', 'desc');

        $perPage     = (int) ($request->integer('perPage') ?: 6);
        $actividades = $q->paginate($perPage)->appends($request->query());
        $registros   = ActividadResource::collection($actividades)->resolve();

        return response()->json([
            'registros' => $registros,
            'enlaces'   => [
                'primero'   => $actividades->url(1),
                'ultimo'    => $actividades->url($actividades->lastPage()),
                'anterior'  => $actividades->previousPageUrl(),
                'siguiente' => $actividades->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * POST /api/actividades
     */
    public function store(ActividadRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Fecha de asignación siempre hoy (MX)
        $data['fechaAsignacion'] = Carbon::now('America/Mexico_City')->toDateString();

        // Normaliza a string por si te llega ObjectId desde otra parte
        $data['docente_id'] = (string) $data['docente_id'];
        $data['tecnica_id'] = (string) $data['tecnica_id'];

        // Normaliza participantes
        if (isset($data['participantes']) && is_array($data['participantes'])) {
            $data['participantes'] = array_values(array_map(function ($p) {
                return [
                    'user_id' => (string) ($p['user_id'] ?? ''),
                    'estado'  => in_array(($p['estado'] ?? 'Pendiente'), ['Pendiente', 'Completado', 'Omitido'], true)
                        ? $p['estado'] : 'Pendiente',
                ];
            }, $data['participantes']));
        }

        $actividad = Actividad::create($data);

        return response()->json([
            'mensaje'   => 'Actividad creada correctamente.',
            'actividad' => new ActividadResource($actividad),
        ], 201);
    }

    public function show(Actividad $actividad): JsonResponse
    {
        return response()->json([
            'actividad' => new ActividadResource($actividad),
        ], 200);
    }

    public function update(ActividadRequest $request, Actividad $actividad): JsonResponse
    {
        $data = $request->validated();

        // Mantén fechaAsignacion si no se envía
        $data['fechaAsignacion'] = $data['fechaAsignacion'] ?? $actividad->fechaAsignacion;
        $data['docente_id']      = (string) $data['docente_id'];
        $data['tecnica_id']      = (string) $data['tecnica_id'];

        if (isset($data['participantes']) && is_array($data['participantes'])) {
            $data['participantes'] = array_values(array_map(function ($p) {
                return [
                    'user_id' => (string) ($p['user_id'] ?? ''),
                    'estado'  => in_array(($p['estado'] ?? 'Pendiente'), ['Pendiente', 'Completado', 'Omitido'], true)
                        ? $p['estado'] : 'Pendiente',
                ];
            }, $data['participantes']));
        }

        $actividad->update($data);

        return response()->json([
            'mensaje'   => 'Actividad actualizada correctamente.',
            'actividad' => new ActividadResource($actividad),
        ], 200);
    }

    public function destroy(Actividad $actividad): JsonResponse
    {
        $actividad->delete();

        return response()->json([
            'mensaje' => 'Actividad eliminada correctamente.',
        ], 200);
    }

    /* ======================= ENDPOINTS PROFESOR ======================= */

    /**
     * GET /api/actividades/mis-cohortes
     */
    public function misCohortes(Request $request): JsonResponse
    {
        try {
            $u = auth('api')->user();
            if (!$u) return response()->json(['cohortes' => []], 200);

            $cohortes = [];

            // 1) Embebido en user.persona
            $raw = $u->persona->cohorte ?? $u->persona['cohorte'] ?? null;
            if ($raw) {
                if (is_array($raw)) {
                    foreach ($raw as $c) { $s = trim((string)$c); if ($s !== '') $cohortes[] = $s; }
                } elseif (is_string($raw)) {
                    $s = trim($raw); if ($s !== '') $cohortes[] = $s;
                }
            }

            // 2) Relación persona
            if (empty($cohortes) && !empty($u->persona_id)) {
                $p = Persona::find($u->persona_id);
                if ($p) {
                    $raw = $p->cohorte ?? null;
                    if (is_array($raw)) {
                        foreach ($raw as $c) { $s = trim((string)$c); if ($s !== '') $cohortes[] = $s; }
                    } elseif (is_string($raw)) {
                        $s = trim($raw); if ($s !== '') $cohortes[] = $s;
                    }
                }
            }

            $cohortes = array_values(array_unique($cohortes));
            return response()->json(['cohortes' => $cohortes], 200);
        } catch (\Throwable $e) {
            return response()->json(['cohortes' => []], 200);
        }
    }

    /**
     * GET /api/actividades/mis-alumnos?cohorte=...
     */
    public function misAlumnos(Request $request): JsonResponse
    {
        try {
            $u = auth('api')->user();
            if (!$u) return response()->json(['alumnos' => []], 200);

            // 1) Cohortes del profe
            $cohortes = $this->cohortesDe($u);

            // 2) Filtro opcional exacto
            $fCoh = trim((string)$request->query('cohorte', ''));
            if ($fCoh !== '') {
                $cohortes = array_values(array_filter($cohortes, fn($c) => strcasecmp($c, $fCoh) === 0));
            }

            if (empty($cohortes)) return response()->json(['alumnos' => []], 200);

            // 3) Alumnos en cualquiera de esas cohortes (string o array)
            $usersQ = User::query()
                ->whereIn('rol', ['estudiante','alumno'])
                ->where(function ($w) use ($cohortes) {
                    // $in sobre persona.cohorte (match si el campo es string == valor o array que contiene valor)
                    $w->whereIn('persona.cohorte', $cohortes);
                });

            $students = $usersQ->get(['_id','id','name','email','matricula','rol','persona']);
            $alumnos = $students->map(function ($u) {
                return [
                    '_id'       => (string)($u->_id ?? $u->id ?? ''),
                    'id'        => (string)($u->id ?? $u->_id ?? ''),
                    'name'      => (string)($u->name ?? ''),
                    'email'     => (string)($u->email ?? ''),
                    'matricula' => (string)($u->matricula ?? ''),
                    'rol'       => (string)($u->rol ?? ''),
                    'persona'   => is_array($u->persona) ? $u->persona : (array)($u->persona ?? []),
                ];
            })->values()->all();

            return response()->json(['alumnos' => $alumnos], 200);
        } catch (\Throwable $e) {
            return response()->json(['alumnos' => []], 200);
        }
    }

    /* -------------------- helper privado local -------------------- */
    private function cohortesDe(User $u): array
    {
        $out = [];
        $raw = $u->persona->cohorte ?? $u->persona['cohorte'] ?? null;
        if ($raw) {
            if (is_array($raw)) { foreach ($raw as $c) { $s = trim((string)$c); if ($s!=='') $out[]=$s; } }
            elseif (is_string($raw)) { $s=trim($raw); if($s!=='') $out[]=$s; }
        }
        if (empty($out) && !empty($u->persona_id)) {
            $p = Persona::find($u->persona_id);
            if ($p) {
                $raw = $p->cohorte ?? null;
                if (is_array($raw)) { foreach ($raw as $c) { $s = trim((string)$c); if ($s!=='') $out[]=$s; } }
                elseif (is_string($raw)) { $s=trim($raw); if($s!=='') $out[]=$s; }
            }
        }
        return array_values(array_unique($out));
    }
}
