<?php

namespace App\Http\Controllers\Api;

use App\Models\Actividad;
use App\Models\User;
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
     * Filtros: ?docente_id=&desde=YYYY-MM-DD&hasta=YYYY-MM-DD&cohorte=ITI%2010%20A
     * Respuesta: { registros: [...], enlaces: { primero, ultimo, anterior, siguiente } }
     */
    public function index(Request $request): JsonResponse
    {
        $q = Actividad::query();

        // Docente asignador
        if ($docenteId = $request->string('docente_id')->toString()) {
            $q->where('docente_id', (string) $docenteId);
        }

        // Rango de fechas (sobre fechaAsignacion)
        if ($desde = $request->string('desde')->toString()) {
            $q->where('fechaAsignacion', '>=', $desde);
        }
        if ($hasta = $request->string('hasta')->toString()) {
            $q->where('fechaAsignacion', '<=', $hasta);
        }

        // Filtro por cohorte -> cruza participantes.user_id
        if ($cohorte = $request->string('cohorte')->toString()) {
            $cohortUsers = User::query()
                ->where(function ($w) use ($cohorte) {
                    $w->where('persona.cohorte', $cohorte)
                      ->orWhere('persona.cohorte', 'all', [$cohorte]);
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

        // === Fecha de asignación forzada a "hoy" en America/Mexico_City ===
        $data['fechaAsignacion'] = Carbon::now('America/Mexico_City')->toDateString();

        // Normaliza ids a string (Mongo)
        $data['docente_id'] = (string) $data['docente_id'];
        $data['tecnica_id'] = (string) $data['tecnica_id'];

        // Participantes -> asegura estructura
        if (isset($data['participantes']) && is_array($data['participantes'])) {
            $data['participantes'] = array_values(array_map(function ($p) {
                return [
                    'user_id' => (string) ($p['user_id'] ?? ''),
                    'estado'  => in_array(($p['estado'] ?? 'Pendiente'), ['Pendiente', 'Completado', 'Omitido'])
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

        // Mantener fechaAsignacion existente si no viene; si viene, normalizar
        $data['fechaAsignacion'] = $data['fechaAsignacion'] ?? $actividad->fechaAsignacion;

        $data['docente_id'] = (string) $data['docente_id'];
        $data['tecnica_id'] = (string) $data['tecnica_id'];

        if (isset($data['participantes']) && is_array($data['participantes'])) {
            $data['participantes'] = array_values(array_map(function ($p) {
                return [
                    'user_id' => (string) ($p['user_id'] ?? ''),
                    'estado'  => in_array(($p['estado'] ?? 'Pendiente'), ['Pendiente', 'Completado', 'Omitido'])
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
}
