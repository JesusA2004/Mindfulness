<?php

namespace App\Http\Controllers\Api;

use App\Models\Actividad;
use Illuminate\Http\Request;
use App\Http\Requests\ActividadRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActividadResource;

class ActividadController extends Controller
{
    /**
     * Muestra una lista de actividades.
     */
    public function index(Request $request): JsonResponse
    {
        // Paginamos de 6 en 6
        $actividades = Actividad::paginate(6);

        return response()->json([
            'registros' => ActividadResource::collection($actividades)->resolve(),
            'enlaces'   => [
                'primero'   => $actividades->url(1),
                'ultimo'    => $actividades->url($actividades->lastPage()),
                'anterior'  => $actividades->previousPageUrl(),
                'siguiente' => $actividades->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * Guarda una nueva actividad en la base de datos.
     */
    public function store(ActividadRequest $request): JsonResponse
    {
        $actividad = Actividad::create($request->validated());

        return response()->json([
            'mensaje'   => 'Actividad creada correctamente.',
            'actividad' => new ActividadResource($actividad),
        ], 201);
    }

    /**
     * Muestra una actividad específica.
     */
    public function show(Actividad $actividad): JsonResponse
    {
        return response()->json([
            'actividad' => new ActividadResource($actividad),
        ], 200);
    }

    /**
     * Actualiza una actividad existente en la base de datos.
     */
    public function update(ActividadRequest $request, Actividad $actividad): JsonResponse
    {
        $actividad->update($request->validated());

        return response()->json([
            'mensaje'   => 'Actividad actualizada correctamente.',
            'actividad' => new ActividadResource($actividad),
        ], 200);
    }

    /**
     * Elimina la actividad especificada de la base de datos.
     */
    public function destroy(Actividad $actividad): JsonResponse
    {
        $actividad->delete();

        return response()->json([
            'mensaje' => 'Actividad eliminada correctamente.',
        ], 200);
    }
}
